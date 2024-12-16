<?php

namespace App\Http\Controllers\API;

use App\Models\Topic;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController;

class ConsulTopicController extends BaseController
{   
    /**
     * Get List Topic
     *                                                                            
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function index()
    {
        $topics = Topic::select('id', 'topic_name')->paginate(10);    
        return $this->sendResponse('Data seluruh topik berhasil diambil.', $topics);
    }

    
    /**
     * Get Detail Topic
     *
     * @param int  $id                                                                              
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function show($id)
    {
        $topic = Topic::select('id', 'topic_name')->find($id);

        if (!$topic) {
            return $this->sendError('Topik yang dicari tidak ditemukan', [], 404);
        }

        return $this->sendResponse('Topik berhasil ditemukan.', $topic);
    }

    
    /**
     * Store Topic
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function store(Request $request)
    {
        $request->validate([
            'topic_name' => 'required|string|max:50',
        ], [
            'topic_name.required' => 'Nama topik wajib diisi.',
        ]);

        $topic = new Topic();
        $topic->topic_name = $request->topic_name;
        $topic->save();

        return $this->sendResponse('Topik baru berhasil ditambahkan', $topic);
    }

    
    /**
     * Update Topic
     *
     * @param  \Illuminate\Http\Request $request
     * @param int  $id                                                                              
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
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

        $topic->topic_name = $request->topic_name;
        $topic->save();

        return $this->sendResponse('Topik berhasil diperbarui.', $topic);
    }

    
    /**
     * Delete Topic
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function destroy($id)
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return $this->sendError('Topik tidak ditemukan', [], 404);
        }

        // Cek apakah topik sedang digunakan oleh PsikologTopic
        if ($topic->psikolog_topic()->exists() || $topic->consultation()->exists()) {
            return $this->sendError("Topik {$topic->topic_name} sedang digunakan dan tidak dapat dihapus.", [], 400);
        }

        $topicName = $topic->topic_name;
        $topic->delete();

        return $this->sendResponse("Topik '{$topicName}' berhasil dihapus.", null); 
    }

}   
