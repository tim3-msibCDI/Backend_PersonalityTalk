<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TopicController extends BaseController
{
    public function index()
    {
        $categories = Topic::all();
        return $this->sendResponse($categories, 'Kategori berhasil diambil.');
    }

}   
