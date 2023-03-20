<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    //
    function __construct()
    {
        $this->middleware(function ($request, $next) {
            session(['module_active' => 'user']);
            return $next($request);
        });
    }
    function list(Request $request)
    {
        $list_act = ['delete' => 'Xóa tạm thời'];
        $status = $request->input('status');
        if ($status == 'trash') {
            $list_act = [
                'restore' => 'Khôi phục',
                'forceDelete' => 'Xóa vĩnh viễn'
            ];
            $users = User::onlyTrashed()->paginate(5);
        } else {
            $keyword = "";
            if ($request->input('keyword')) {
                $keyword = $request->input('keyword');
            }
            $users = User::where('name', 'LIKE', "%{$keyword}%")->paginate(5);
        }

        $count_user_active = User::count();
        $count_user_trash = User::onlyTrashed()->count();

        $count = [$count_user_active, $count_user_trash];

        // return $users;
        return view('admin.user.list', compact('users', 'count', 'list_act'));
    }
    function add()
    {

        return view('admin.user.add');
    }
    function store(Request $request)
    {
        $request->validate(
            [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ],
            [
                'required' => ':attribute không được để trống',
                'min' => ':attribute có độ dài ít nhất :min ký tự',
                'max' => ':attribute có độ dài tối đa :max ký tự',
                'confirmed' => 'Xác nhận mật khẩu không thành công',
            ],
            [
                'name' => 'Họ và tên',
                'email' => 'Email',
                'password' => 'Mật khẩu',
            ]
        );
        User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);
        return redirect('admin/user/list')->with('status', 'Thêm thành viên thành công!');
    }
    function delete($id)
    {
        if (Auth::id() != $id) {
            $user = User::find($id);
            $user->delete();
            return redirect('admin/user/list')->with('status', 'Bạn đã xóa thành công!');
        } else {
            return redirect('admin/user/list')->with('status', 'Bạn không thể tự xóa mình ra khỏi hệ thống');
        }
    }
    function action(Request $request)
    {
        $list_check = $request->input('list_check');
        if ($list_check) {
            foreach ($list_check as $k => $id) {
                if (Auth::id() == $id) {
                    unset($list_check[$k]);
                }
            }
            if (!empty($list_check)) {
                $act = $request->input('act');
                if ($act == 'delete') {
                    User::destroy($list_check);
                    return redirect('admin/user/list')->with('status', 'Bạn đã xóa thành công!');
                }
                if ($act == 'restore') {
                    User::onlyTrashed()->whereIn('id', $list_check)->restore();
                    return redirect('admin/user/list')->with('status', 'Bạn đã khôi phục thành công!');
                }
                if ($act == 'forceDelete') {
                    User::onlyTrashed()->whereIn('id', $list_check)->forceDelete();
                    return redirect('admin/user/list')->with('status', 'Bạn đã xóa vĩnh viễn thành công!');
                }
            }
            redirect('admin/user/list')->with('status', 'Bạn không thể thao tác trên tài khoản đang đăng nhập!');
        } else {
            return redirect('admin/user/list')->with('status', 'Bạn cần chọn tác vụ để thực thi!');
        }
    }
    function edit($id)
    {
        $user = User::find($id);
        return view('admin.user.edit', compact('user'));
    }
    function update(Request $request, $id)
    {
        $request->validate(
            [
                'name' => 'required|string|max:255',
                'password' => 'required|string|min:8|confirmed',
            ],
            [
                'required' => ':attribute không được để trống',
                'min' => ':attribute có độ dài ít nhất :min ký tự',
                'max' => ':attribute có độ dài tối đa :max ký tự',
                'confirmed' => 'Xác nhận mật khẩu không thành công',
            ],
            [
                'name' => 'Họ và tên',
                'password' => 'Mật khẩu',
            ]
        );
        User::where('id', $id)->update(
            [
                'name' => $request->input('name'),
                'password' => Hash::make($request->input('password')),
            ]
        );
        return redirect('admin/user/list')->with('status', 'Bạn đã cập nhật thành công!');
    }
}
