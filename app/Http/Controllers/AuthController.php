<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;
use Exception;
use DB;

class AuthController extends Controller
{

    protected $account;

    public function __construct(Account $_account) {
        $this->account = $_account;
    }
    public function register(Request $request){
        try{
            $validator = Validator::make($request->all(),
                [
                    'fullname'=>['required', 'regex:/^[\p{L}\p{M}\p{Pd}\p{Zs}\']+$/u'],
                    'username' => ['required', 'regex:/^[a-zA-Z0-9_-]+$/'],
                    'password'=> ['required', 'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'],
                    'email'=>['required','email'],
                    // 'day_of_birth'=>['required'],
                    'gender'=>['required'],
                ]
            );
        if ($validator->fails()) {
            // Xử lý khi có lỗi trong validator
            return response()->error($validator->errors(), 401);
        }

        // $accountModel = new Account();
        if($this->account->checkDuplicate($request->input('username'), $request->input('email'))){
            return response()->error('email hoặc username đã tồn tại.',400);
        }
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $avatar = "default_avt.png";
        $account = Account::create([
            'fullname' => $request->input('fullname'),
            'username' => $request->input('username'),
            'password' => Hash::make($request->input('password')),
            'email' => $request->input('email'),
            'day_of_birth' => date_create($request->input('day_of_birth')),//date('Y-m-d H:i:s', $request->input('day_of_birth')),
            'gender' => $request->input('gender'),
            'created_at' => date('Y-m-d H:i:s'),
            'avatar' => $avatar,
            'cover' => 'default_cover.PNG',
        ]);

        if(!$account){
            return response()->error('Không thể tạo tài khoản.', 500);
        }

        return response()->success($account, 'Tài khoản đã được tạo thành công.', 200);

        }catch(Exception $ex){
            throw $ex;
        }

    }

    public function checkLogin(){
        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            return response()->success(
                [
                    'username'=>$user->username,
                    'email'=>$user->email,
                    'avatar'=>$user->avatar,
                    'phone'=>$user->phone,
                    'location'=>$user->location,
                    'fullname'=>$user->fullname,
                ],
                'Token còn hoạt động',
                200
            );
        }else{
            return response()->success(
                [],
                'Token không tồn tại hoặc đã đăng xuất',
                401
            );
        }
    }


    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(),
                [
                    'login'=>['required', 'string'],
                    'password' => ['required', 'string'],
                ]
            );
             // Kiểm tra xem người dùng đã đăng nhập hay chưa (nó check token trong bảng personal_access_token)
            if (auth('sanctum')->check()) {
                return response()->error("Bạn đã đăng nhập và không thể truy cập API đăng nhập lại.", 403);
            }
            // nếu định dạng là email thì sẽ tự động convert về email để tìm kiếm
            $login_type = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL )
            ? 'email'
            : 'username';

            $request->merge([
                $login_type => $request->input('login')
            ]);
            // Xác thực người dùng và lấy thông tin người dùng
            if (Auth::attempt($request->only($login_type, 'password'))) {
                $user = Auth::user();
                if($user->status == "BLOCK"){
                    return response()->error('Tài khoản của bạn đã bị khóa !', 403);
                }
                // Tạo token Sanctum cho người dùng
                $token = $user->createToken('token-name')->plainTextToken;

                $result = [
                    'data'=>[
                        'username'=>$user->username,
                        'email'=>$user->email,
                        'avatar'=>$user->avatar,
                        'phone'=>$user->phone,
                        'location'=>$user->location,
                        'fullname'=>$user->fullname,
                    ],
                    'authentication' => [
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                    ]
                ];
                return response()->success($result,"Đăng nhập thành công !",200);
            }
            // Xác thực thất bại

            // Kiểm tra xem tài khoản tồn tại trong cơ sở dữ liệu
            $userExists = Account::where($login_type, $request[$login_type])->first();
            if ($userExists) {
                // Nếu tài khoản tồn tại, thì sai mật khẩu
                return response()->error('Sai mật khẩu !', 401);
            } else {
                // Nếu tài khoản không tồn tại, thì sai tài khoản
                return response()->error('Tài khoản không tồn tại !', 402);
            }
        } catch (Exception $th) {
            throw $th;
        }
    }

    public function logout(Request $request)
    {
        Auth::user()->tokens->each(function ($token, $key) {
            $token->delete();
        });

        return response()->success([],"Logout successful",200);
    }

    public function show($image)
    {
        $path = storage_path('app/public/media/' . $image);

        if (!File::exists($path)) {
            abort(404);
        }

        $file = File::get($path);
        $type = File::mimeType($path);

        $response = response($file, 200)->header("Content-Type", $type);

        return $response;
    }

    public function uploadFile(Request $request){
        try {
           // up ảnh
           $imageInfo = array();
           if ($request->hasFile('media')) {
               $images = $request->file('media');
               foreach ($images as $image) {
                   $extension = $image->getClientOriginalExtension();
                   $randomString = uniqid();
                   $imageName = time() . '' . $randomString . '.' . $extension;
                   $image->move(storage_path('app/public/media/'), $imageName);
                   $imageInfo[] = ['type' => $image->getClientOriginalExtension(),'name' => $imageName];
               }
               return response()->success(['file_info' => $imageInfo],'Tải lên ảnh thành công',200);
           }
           return response()->error('Tải lên ảnh thất bại !',400);
        } catch (Throwable $th) {
            throw $th;
        }
    }
}
