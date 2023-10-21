<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Post;
use App\Models\Account;
use App\Models\Report;
use Exception;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    protected $post;
    protected $account;
    protected $report;

    public function __construct(Post $_post, Account $_account, Report $_report) {
        $this->post = $_post;
        $this->account = $_account;
        $this->report = $_report;
    }

    public function getOverview(Request $request) {
        try {
            // Lấy số lượng tài khoản tạo mới trong 7 ngày gần nhất
            $numNewAccount = $this->account->getNumNewAccount();

            // Lấy số lượng bài viết mới trong 7 ngày gần nhất
            $numNewPost = $this->post->getNumNewPost();

            // Số lượng lượt report mới trong 7 ngày gần nhất
            $numNewReport = $this->report->getNumNewReport();

            // Số lượng account bị khóa trong 7 ngày gần nhất
            $numNewBlock = $this->account->getNumNewBlock();

            // Thông tin tỉ lệ độ tuổi của ng dùng
            $listAgeRange = $this->account->getNumAccByAge();

            // Thông tin số lượng tài khoản tạo mới trong 10 ngày gần nhất
            $numNewAccChart = $this->account->getNumNewAccountByDate();

            // Thông tin số lượng bài viết tạo mới trong 10 ngày gần nhất
            $numNewPostChart = $this->post->getNumNewPostByDate();

            // Thông tin số lượng report mới trong 10 ngày gần nhất
            $numNewReportChart = $this->report->getNumReportByDate();

            // Trả về response thành công
            return response()->success([
                "num_new_acc"=>$numNewAccount,
                "num_new_post"=>$numNewPost,
                "num_new_report"=>$numNewReport,
                "num_new_block"=>$numNewBlock,
                "list_age_range"=>$listAgeRange,
                "num_new_acc_chart"=>$numNewAccChart,
                "num_new_post_chart"=>$numNewPostChart,
                "num_new_report_chart"=>$numNewReportChart
            ],
                "Lấy dữ liệu thành công",
                200);

        } catch (Exception $e) {
            throw $e;
            return response()->error("đã xảy ra lỗi", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function getReportedPost(Request $request) {

        $validator = Validator::make($request->all(),
        [
         'page_size'=>'required|string',
         'page_index'=>'required|string',
        ]);

        if ($validator->fails()) {
            // Xử lý khi validation thất bại, ví dụ trả về lỗi
            return response()->error($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $pageSize = $request->input('page_size');
            $pageIndex = $request->input('page_index');
            $listReportedPost = $this->report->getListReportedPost($pageIndex, $pageSize);

            // Trả về response thành công
            return response()->success($listReportedPost,
                "Lấy dữ liệu thành công",
                200);

        } catch (Exception $e) {
            throw $e;
            return response()->error("đã xảy ra lỗi", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getReportedAcc(Request $request) {
        $validator = Validator::make($request->all(),
        [
         'page_size'=>'required|string',
         'page_index'=>'required|string',
        ]);

        if ($validator->fails()) {
            // Xử lý khi validation thất bại, ví dụ trả về lỗi
            return response()->error($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $pageSize = $request->input('page_size');
            $pageIndex = $request->input('page_index');
            $listReportedAcc = $this->account->getListReportedAcc($pageIndex, $pageSize);

            // Trả về response thành công
            return response()->success($listReportedAcc,
                "Lấy dữ liệu thành công",
                200);

        } catch (Exception $e) {
            throw $e;
            return response()->error("đã xảy ra lỗi", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
