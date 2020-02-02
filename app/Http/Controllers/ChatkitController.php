<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatkitController extends Controller
{
    private $chatkit;
    
    public function __construct()
    {
        $this->chatkit = app('ChatKit');
    }

    public static function getChatkitAlertInformation() {
        $curUser = Auth::user();
        if ($curUser->isAdmin()) {
            $curUserChatId = "admin";
        } else {
            $curUserChatId = "customer_".$curUser->id;
        }
        $chatkitLocator = config('services.chatkit.locator');
        
        $chatkit = app('ChatKit');
        $unreadCnt = 0;
        $userIds = array();

        $rooms = $chatkit->getUserRooms([ 'id' => $curUserChatId ]);
        if (isset($rooms) && $rooms["status"] == 200) {
            $rooms = $rooms["body"];
            foreach($rooms as $key=>$room) {
                $unreadCnt += $room["unread_count"];
                $userIds = array_merge($userIds, $room["member_user_ids"]);
                foreach($userIds as $id) {
                    $cursor = $chatkit->getReadCursor([
                        'user_id' => $id,
                        'room_id' => $room["id"]
                      ]);
                    if (isset($cursor) && $cursor["status"] == 200) {
                        $rooms[$key]["cursors"][$id] = $cursor["body"];
                    }
                }
            }
        } else {
            $rooms = null;
        }

        $userIds = array_unique($userIds);
        if (count($userIds) > 0) {
            $users = $chatkit->getUsersByID([
                'user_ids' => $userIds
              ]);
    
            if (isset($users) && $users["status"] == 200) {
                $usersInfo = $users["body"];
            }
        }

        return ["chatId" => $curUserChatId, 
                "chatkitLocator" => $chatkitLocator,
                "unreadCnt" => $unreadCnt,
                "rooms" => $rooms,
                "users" => $usersInfo ];
    }

    /**
     * Show the application chat room.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function chat(Request $request)
    {
        $chatkitId = $request->session()->get('chatkit_id');
        $rooms = $this->getRooms($chatkitId);
        $userIds = array();

        foreach($rooms as $key=>$room) {
            $members = $room["member_user_ids"];
            $userIds = array_merge($userIds, $members);

            foreach($members as $member) {
                $cursor = $this->chatkit->getReadCursor([
                    'user_id' => $member,
                    'room_id' => $room["id"]
                  ]);
                if (isset($cursor) && $cursor["status"] == 200) {
                    $rooms[$key]["cursors"][$member] = $cursor["body"];
                }
            }
        }

        $userIds = array_unique($userIds);
        $users = $this->getUsersByIds($userIds);

        $curUser = Auth::user();
        if ($curUser->isAdmin()) {
            $curUserChatId = "admin";
        } else {
            $curUserChatId = "customer_".$curUser->id;
        }
        foreach($users as $user) {
            if ($curUserChatId == $user["id"]) {
                $curUser = $user;
            }
        }

        $chatkitLocator = config('services.chatkit.locator');
        return view('chat')->with(compact('rooms', 'users', 'curUser', 'chatkitLocator'));
    }

    /**
     * Receives a client request and provides a new token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function chatkitAuthenticate(Request $request)
    {
        $response = $this->chatkit->authenticate([
            'user_id' => $request->user_id,
        ]);

        return response()
            ->json(
                $response['body'],
                $response['status']
            );
    }

    /**
     * Send user message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function sendMessage(Request $request)
    {
        $message = $this->chatkit->sendSimpleMessage([
            'sender_id' => $request->user,
            'room_id' => $request->room,
            'text' => $request->message
        ]);

        return response()
            ->json(
                $message['body']
            );
    }

    /**
     * Get all users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function getAllUsers()
    {
        $users = $this->chatkit->getUsers();

        return response($users);
    }

    /**
     * Get users by ids.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function getUsersByIds($ids)
    {
        $users = $this->chatkit->getUsersByID([
            'user_ids' => $ids
          ]);
        if (!isset($users) || $users["status"] != 200) {
            return array();
        }

        return $users["body"];
    }

    /**
     * Create a new room.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function createRoom($id, $name, $avatar)
    {
        $customerId = "customer_$id";
        $adminId = "admin";
        $room_id = "room_$id";
        $avatarUrl = url("/") . $avatar;
        $this->createUser($customerId, $name, $avatar);
        
        $room = $this->chatkit->createRoom([
            'id' => $room_id,
            'creator_id' => $customerId,
            'name' => 'Admin Assistance ' . $id,
            'user_ids' => [$adminId, $customerId]
          ]);

        return response($room);
    }

    /**
     * Create a new chat user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function createUser($id, $customer_name, $avatar) {
        $this->chatkit->createUser([
            'id' =>  $id,
            'name' => $customer_name,
            'avatar_url' => $avatar
        ]);
    }

    /**
     * Get all rooms.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function getRooms($chatkitId)
    {
        $rooms = $this->chatkit->getUserRooms([ 'id' => $chatkitId ]);
        
        if (!isset($rooms) || $rooms["status"] != 200) {
            return array();
        }
        $rooms = $rooms["body"];
        return $rooms;
    }

    public function addChatkitSession(Request $request) {
        $user = Auth::user();
        if ($user->isAdmin()) {
            $chatkitId = "admin";
        } else {
            $chatkitId = "customer_" . Auth::user()->id;
        }
        
        $request->session()->put('chatkit_id', $chatkitId);
    }
        // /**
    //  * This is not used in this Chat app
    //  * The user joins chat room.
    //  *
    //  * @param  \Illuminate\Http\Request $request
    //  * @return mixed
    //  */
    // public function join(Request $request)
    // {
    //     $chatkit_id = strtolower(str_random(5));

    //     // Create User account on Chatkit
    //     $this->chatkit->createUser([
    //         'id' =>  $chatkit_id,
    //         'name' => $request->username,
    //     ]);

    //     $this->chatkit->addUsersToRoom([
    //         'room_id' => $this->roomId,
    //         'user_ids' => [$chatkit_id],
    //     ]);

    //     // Add User details to session
    //     $request->session()->push('chatkit_id', $chatkit_id);

    //     // Redirect user to Chat Page
    //     return redirect(route('chat'));
    // }

}
