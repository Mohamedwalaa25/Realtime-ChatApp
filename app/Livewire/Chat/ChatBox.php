<?php

namespace App\Livewire\Chat;

use App\Models\Message;
use App\Notifications\MessageRead;
use App\Notifications\MessageSent;
use Livewire\Component;

class ChatBox extends Component
{
    public $selectedConversation;
    public $body;
    public $loadedMessages;
    public $paginate_var = 10;


    public function getListeners()
    {

        $auth_id = auth()->user()->id;

        return [

            'loadMore',
            "echo-private:users.{$auth_id},.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated" => 'broadcastedNotifications'

        ];
    }

    public function broadcastedNotifications($event)
    {


        if ($event['type'] == MessageSent::class) {

            if ($event['conversation_id'] == $this->selectedConversation->id) {

                $this->dispatch('scroll-bottom');

                $newMessage = Message::find($event['message_id']);


                #push message
                $this->loadedMessages->push($newMessage);
                $newMessage->read_at = now();
                $newMessage->save();

                #broadcast
                $this->selectedConversation->getReceiver()
                    ->notify(new MessageRead($this->selectedConversation->id));
            }
        }
    }
    public function loadedMessage()
    {
        $this->loadedMessages = Message::query()->where('conversation_id', $this->selectedConversation->id)->get();
    }


    public
    function sendMessage()
    {
        $this->validate(['body' => 'required|string']);

        $createdMessage = Message::create([
            'body' => $this->body,
            'conversation_id' => $this->selectedConversation->id,
            'sender_id' => auth()->user()->id,
            'receiver_id' => $this->selectedConversation->getReceiver()->id
        ]);

        $this->reset('body');
        $this->loadedMessages->push($createdMessage);

        $this->dispatch("scroll-bottom");

        $this->selectedConversation->updated_at = now();
        $this->selectedConversation->save();

        $this->dispatch("refresh")->to("chat.chat-list");

        #brpdcast
        $this->selectedConversation->getReceiver()
            ->notify(new MessageSent(
                Auth()->User(),
                $createdMessage,
                $this->selectedConversation,
                $this->selectedConversation->getReceiver()->id

            ));
    }

    public
    function mount()
    {
        $this->loadedMessage();
    }

    public
    function render()
    {
        return view('livewire.chat.chat-box');
    }
}
