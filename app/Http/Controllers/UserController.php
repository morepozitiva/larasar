<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use neto737\BitGoSDK\Enum\CurrencyCode;
use neto737\BitGoSDK\BitGoSDK;
use Illuminate\Http\Request;
use App\User;
use Hash;
use Auth;
use DB;

/**
 * Tags: UserModule - BitgoModule
 *
 * @for UserController - Profile.vue - Users.vue
 */
class UserController extends Controller
{

    use AuthenticatesUsers;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      // return User::all();
      // return DB::table('users')->get();
      $bitgo = new BitGoSDK(env('YOUR_API_KEY_HERE'), CurrencyCode::BITCOIN, false);
      $bitgo->walletId = env('YOUR_WALLET_ID_HERE');//TagIndex: BitgoModule
      return$createAddress = $bitgo->createWalletAddress();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return 'create';
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    { //return $request;
      if ($request->locale) config(['app.locale' => $request->locale]);
      if ($request->api) {

        $this->validate($request, [
            'role' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $seed = str_split('abcdefghijklmnopqrstuvwxyz'
            .'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
            .'0123456789!@#$%^&*()'); // and any other characters
        shuffle($seed); // probably optional since array_is randomized; this may be redundant
        $rand = ''; foreach (array_rand($seed, 8) as $k) $rand .= $seed[$k];

        $user = User::create([
            'user_id' => $request->auth['id'],
            'role' => $request->role,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]); //TagStore: UserModule

        $content_role = [
            'title'=> 'The '.$request->auth['role'].' Added You As '.$request['role'],
            'body'=> 'Please use your email: '.$request['email'].' and password: '.$rand.' to login',
            'button' => 'Click Here',
            'url' => env('APP_URL').'/login'
        ];  //Mail::to($request->email)->send(new InfoOfatv($content_role));

        // if($request->form == 'admin')
        return $request['role'].' Created Successfully';
        // return back()->with('status', $request['role'].' Created Successfully');
      }
      return [
        'appName' => config('app.name'),
        'locale' => app()->getLocale(),
        'locales' => config('app.locales'),
        // 'services' => config('services'),
      ];
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, User $user)
    { //$request->avatar;
      $admins = $user->id == 1 || $user->role == 'Admin'?1:0;
      $sellers = $user->role == 'Seller'?1:0;
      $buyers = $user->role == 'Buyer'?1:0;//TagShow: UserModule
      if($request->avatar) return $user->new['avatar'];
      if($admins) return DB::table('users')->get();
      if($sellers) return DB::table('users')->where('user_id', $user['id'])->get();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    { //return$request->get('avatar');

      $put = User::find($id);
      $check = Auth::validate([
          'email'    => $put->email,
          'password' => $request->password
      ]); $file = $request->file('avatar');
      if ($request->role) $put->role = $request->role;
      if ($request->name) $put->name = $request->name;
      if ($request->email) $put->email = $request->email;
      if ($request->phone) $put->phone = $request->phone;
      if ($request->address) $put->address = $request->address;
      if ($request->city) $put->city = $request->city;
      if ($request->zip_code) $put->zip_code = $request->zip_code;
      if ($request->pwd || $request->update_password) {
          $this->validate($request, [
            // 'new_password' => ['required', 'string', 'min:8', 'confirmed'],
          ]);
          if ($request->update_password) {
            $request->new_password = $request->update_password;
            $check = true;
          }
          if (!$check) {
            return ['success' => 'Current Password Do Not Match Our Record'];
            // return back()->with('status', 'Current Password Do Not Match Our Record');
          }
          if (!$request->new_password || $request->new_password != $request->password_confirmation) {
            return ['success' => 'Password Confirmation Do Not Match'];
            // return back()->with('status', 'Password Confirmation Do Not Match');
          }   $put->password = bcrypt($request->new_password);
      }   if ($request->hasFile('avatar')) {
            $FileName = $file->getClientOriginalName();
            $path = $file->storeAs('images/profile', $id.'jpg');
            $file->move('images/profile', $id.'jpg');
            $put->avatar = $path;
      }//https://appdividend.com/2018/02/13/vue-js-laravel-file-upload-tutorial/
      if ($request->get('avatar')) {
        $avatar = $request->get('avatar');
        $name = time().'.' . explode('/', explode(':', substr($avatar, 0, strpos($avatar, ';')))[1])[1];
        \Image::make($avatar)->save(public_path('images/profile/').$name);
        $put->avatar = 'images/profile/'.$name;
        // $image= new FileUpload();
        // $image->image_name = $name;
        // $image->save();

        // return 'You have successfully uploaded an image';
      } $put->update();//TagUpdate: UserModule
      return response()->json([
        'success' => 'Updated successfully',
        'user' => $put
      ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    { //return $request;
      if ($request->avatar) {
        $user = User::where('id', $id);
        $user->update(['avatar' => 'images/profile/default.jpg']);
        return [
          'success' => 'Image Deleted Successfully',
          'user' => $user->first()
        ];
      }
      if ($id == 1 || Auth::id() == $id)
        return 'You Cannot Delete Super Admin or Your Own Account';
        //back()->with('status', 'You Cannot Delete Super Admin or Your Own Account');
      else User::destroy($id);return 'User Deleted Successfully';//TagDestroy: UserModule
    }
}
