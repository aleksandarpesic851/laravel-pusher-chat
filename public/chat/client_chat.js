var totalUnreadCnt = 0;
var curCursor;

$(document).ready(function() {
    $('.submit').click(function() {
        updateSelfCursor();
        sendMessage();
    });
    
    $(".message-input input").on('keydown', function(e) {
        updateSelfCursor();
        if (e.which == 13) {
            sendMessage();
            return false;
        } else if (chatkitUser && chatkitRoom) {
            chatkitUser.isTypingIn({ roomId: chatkitRoom.id });    // Notify Typing
        }
    });

    $(".message-input input").on('focus', function() {
        updateSelfCursor();
    });
    
    // when document loaded, activate first element so that load it's chat
    $("#contacts li").first().addClass("active");
    loadMessages();

    initChatkit();
    // initUnreadCounts();
});

// set cursor, update cursor
function updateSelfCursor() {
    if (!chatkitUser || !chatkitRoom || chatkitRoom.messages.length < 1) {
        return;
    }
    
    const position = chatkitRoom.messages[chatkitRoom.messages.length - 1].id;
    let selfCursor = chatkitRoom.cursors[curUser.id];

    // current user's cursor is not latest, update it.
    if (selfCursor && selfCursor.position == position) {
        return;
    }
    chatkitUser.setReadCursor({
        roomId: chatkitRoom.id,
        position: position
    });

    if (selfCursor) {
        chatkitRoom.cursors[curUser.id].position = position;
    } else {
        chatkitRoom.cursors[curUser.id] = {position: position};
    }

    $("#" + chattingUser.id + " .unread").hide();
}

// update left unread counts
// user id: updateing user id.

function updateUnreadCount(userId) {
    let unreadElement = $("#" + userId + " .unread");
    
    let room = getRoom(userId);
    let count = 0, startIdx = 0;
    let selfCursorPosition = -1;
    if (room.cursors[curUser.id]) {
        selfCursorPosition = room.cursors[curUser.id].position;
    }
    
    if (selfCursorPosition != -1) {
        startIdx = room.messages.findIndex(message => message.id == selfCursorPosition)+1;
    }

    if (startIdx > 0) {
        let i = 0;
        for ( i = startIdx; i < room.messages.length; i++) {
            if (room.messages[i].senderId == userId) {
                count++;
            }
        }
    }

    if (count > 0) {
        unreadElement.show();
        unreadElement.html(count);
    } else {
        unreadElement.hide();
    }
}

// add message test to content element.
// isSameRoom : if true, add message to chat content elemtn
function addNewMessage(message, isSameRoom) {
    let sendTypeClass = "received";
    let avatar_url = chattingUser.avatar_url;
    if (message.senderId == curUser.id) {
        sendTypeClass = "sent";
        avatar_url = curUser.avatar_url;
        if (isSameRoom) {
            $('.messages ul').append('<li class="' + sendTypeClass + '"><img src="' + avatar_url + '"/><p>' + message.text + '</p></li>');
            $(".chat-content .messages").scrollTop(9999);

            // if chatting user cursor is same with current message id, add cursor element.
            let cursor = chatkitRoom.cursors[chattingUser.id];
            if (cursor && message.id == cursor.position) {
                addCursorElement();
            }
        }
    } else {
        // Update unread mark in left menu
        updateUnreadCount(message.senderId);
        if (isSameRoom) {
            $('.messages ul').append('<li class="' + sendTypeClass + '"><img src="' + avatar_url + '"/><p>' + message.text + '</p></li>');
            $(".chat-content .messages").scrollTop(9999);
            addCursorElement();
        }
        $('#' + message.senderId + ' .preview').html(message.text);
    }
};

// Add cursor in content element.
// cursor: new updated cursor
function updateCursor(cursor) {

    // Updated cursor is for current user, not apply
    // because it's not needed to show
    if (cursor.userId == curUser.id) {
        return;
    }

    // if the changed cursor is for current chatting room, update cusor element
    if (cursor.room.id == chatkitRoom.id) {
        addCursorElement();
    }
}

// add cursor element in chat content
function addCursorElement() {
    $(".messages ul .cursor").parent().remove();
    $('.messages ul').append('<li class="sent"><img src="' + chattingUser.avatar_url + '" class="cursor"/></li>');
    $(".chat-content .messages").scrollTop(9999);
}

// change room and update message when user click contact user
function changeRoom(elementID) {
    let curActiveId = $("#contacts li.active").attr("id");
    if (elementID == curActiveId) {
        return;
    }

    $("#contacts li.active").removeClass("active");
    $("#" + elementID).addClass("active");
    $('.messages ul').empty();
    loadMessages();
}

function loadMessages() {
    let userId = $("#contacts li.active").attr("id");
    chatkitRoom = getRoom(userId);
    chattingUser = getChattingUser(chatkitRoom);

    $(".chat-content .contact-profile #avatar").attr("src", chattingUser.avatar_url);
    $(".chat-content .contact-profile p").html(chattingUser.name);

    if (chatkitRoom.messages) {
        let lastMessage = "";
        let chattingUserCursor = chatkitRoom.cursors[chattingUser.id];

        chatkitRoom.messages.forEach(message => {
            let bSend = false;
            let sendTypeClass = "received";
            let avatar_url = chattingUser.avatar_url;
            if (message.senderId == curUser.id) {
                bSend = true;
                sendTypeClass = "sent";
                avatar_url = curUser.avatar_url;
            }
            
            $('.messages ul').append('<li class="' + sendTypeClass + '"><img src="' + avatar_url + '" alt="" /><p>' + message.text + '</p></li>');
            
            if (!bSend) {
                lastMessage = message.text;
            }

            if ((chattingUserCursor && chattingUserCursor.position == message.id) || message.senderId == chattingUser.id){
                addCursorElement();
            }
        });
        
        $('.contact.active .preview').html(lastMessage);
        // $(".chat-content .messages").scrollTop($(".chat-content .messages")[0].scrollHeight);
        $(".chat-content .messages").animate({ scrollTop: $(".chat-content .messages")[0].scrollHeight }, "fast");
    }

}

// get room, in which userId is a member
function getRoom(userId) {
    return rooms.find(room => room.member_user_ids.find(id => id == userId));
    // let roomCnt = rooms.length, memberCnt, i, k;
    // for (i = 0 ; i < roomCnt; i ++) {
    //     memberCnt = rooms[i].member_user_ids.length;
    //     for (k = 0 ; k < memberCnt; k ++) {
    //         if (rooms[i].member_user_ids[k] == userId) {
    //             return rooms[i];
    //         }
    //     }
    // }
}

function getChattingUser(room) {
    let members = room.member_user_ids;
    let i = 0;
    let chattingUserId;
    for (i = 0 ; i< members.length; i ++) {
        if (members[i] != curUser.id) {
            chattingUserId = members[i];
            break;
        }
    }

    return users.find(user => user.id == chattingUserId);
}

function initChatkit() {
    const tokenProvider = new Chatkit.TokenProvider({
        url: `/api/live_chat/authenticate`
    });
    const chatManager = new Chatkit.ChatManager({
        instanceLocator: chatkitLocator,
        userId: curUser.id,
        tokenProvider,
    });
    chatManager.connect()
        .then(user => {
            chatkitUser = user;
            subscribeToRooms();

            // chatkitUser.enablePushNotifications()
            //     .then(() => {
            //         console.log('Push Notifications enabled');
            //     })
            //     .catch(error => {
            //         console.error("Push Notifications error:", error);
            //     });
        })
        .catch(error => {
            console.log('Error on connection', error)
        });
}

function subscribeToRooms() {
    if (!chatkitUser) {
        return;
    }
    let i = 0;
    for (i = 0 ; i < rooms.length; i ++) {
        rooms[i]["messages"] = [];
        
        chatkitUser.subscribeToRoomMultipart({
            roomId: rooms[i].id,
            hooks: {
                onMessage: message => {
                    let newMessage = {
                        id: message.id,
                        senderId: message.senderId,
                        text: message['parts'][0]['payload']['content']
                        // timestamp: message.createdAt
                    };

                    rooms[rooms.findIndex(room=>room.id == message.roomId)]["messages"].push(newMessage);
                    const isSameRoom = chatkitRoom.id == message.roomId;
                    addNewMessage(newMessage, isSameRoom);
                },
                onPresenceChanged: (state, user) => {
                    if (state.current == "online") {
                        $("#" + user.id + " span").removeClass("away").addClass("online");
                    } else {
                        $("#" + user.id + " span").removeClass("online").addClass("away");
                    }
                    console.log(`User ${user.name} is ${state.current}`);
                },
                onUserStartedTyping: user => {
                    // if current user is chatting with typing user, show typing effect on message content
                    if (chattingUser.id == user.id) {
                        $(".chat-content .contact-profile .typing").show();
                    } else {
                        $('#' + user.id + ' .meta img').show();
                        $('#' + user.id + ' .meta div').hide();
                    }
                },
                onUserStoppedTyping: user => {
                    if (chattingUser.id == user.id) {
                        $(".chat-content .contact-profile .typing").hide();
                    }
                    $('#' + user.id + ' .meta img').hide();
                    $('#' + user.id + ' .meta div').show();
                },
                onNewReadCursor: cursor => {
                    rooms.find(room => room.id == cursor.room.id).cursors[cursor.userId] = cursor;
                    updateCursor(cursor);
                }
            },
            messageLimit: 100
        });
    }
}

function sendMessage() {
    let message = $(".message-input input").val();
	if($.trim(message) == '') {
		return false;
    }
    $('.message-input input').val(null);
    chatkitUser.sendSimpleMessage({
        roomId: chatkitRoom.id,
        text: message,
    })
    .then(messageId => {
        let newMessage = {
            id: messageId,
            senderId: curUser.id,
            text: message
        };
        chatkitRoom.messages.push(newMessage);
    })
    .catch(err => {
        console.log(`Error adding message` + err);
    });
}