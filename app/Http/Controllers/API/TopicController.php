<?php

namespace App\Http\Controllers\API;

use App\Models\Topic;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController;

class TopicController extends BaseController
{
    public function index()
    {
        $topics = Topic::all();
        return $this->sendResponse('Data seluruh topik berhasil diambil.', $topics);
    }

    public function show($id)
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return $this->sendError('Topik yang dicari tidak ditemukan', [], 404);
        }

        return $this->sendResponse('Topik berhasil ditemukan.', $topic);
    }

    public function store(Request $request)
    {
        $request->validate([
            'topic_name' => 'required|string|max:50',
        ], [
            'topic_name.required' => 'Nama topik wajib diisi.',
        ]);

        $topic = Topic::create([
            'topic_name' => $request->topic_name, 
        ]);

        return $this->sendResponse('Topik baru berhasil ditambahkan', $topic);
    }

    public function update(Request $request, $id)
    {
        $topic = Topic::find($id);
        // dd($topic);

        if (!$topic) {
            return $this->sendError('Topik tidak ditemukan', [], 404);
        }

        $request->validate([
            'topic_name' => 'required|string|max:50',
        ], 
        [
            'topic_name.required' => 'Nama topik wajib diisi.',
        ]);

        $topic->update([
            'topic_name' => $request->topic_name, 
        ]);

        return $this->sendResponse('Topik berhasil diperbarui.', $topic);
    }

    public function destroy($id)
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return $this->sendError('Topik tidak ditemukan', [], 404);
        }

        // Cek apakah topik sedang digunakan oleh PsikologTopic
        if ($topic->psikolog_topic()->exists()) {
            return $this->sendError("Topik '{$topic->topic_name}' sedang digunakan oleh psikolog dan tidak dapat dihapus.", [], 400);
        }

        $topicName = $topic->topic_name;
        $topic->delete();

        return $this->sendResponse("Topik '{$topicName}' berhasil dihapus.", null); 
    }

}   
