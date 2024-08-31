<?php

namespace App\Livewire\Chat;

use App\Models\Message;
use Livewire\Component;

class ChatBox extends Component
{
    public $selectedConversation;
    public $body;
    public $loadedMessage;
    public $paginate_var=10;

    public function loadedMessage()
    {
        $this->loadedMessage=Message::query()->where('conversation_id', $this->selectedConversation->id)->get();
    }



    public function sendMessage()
    {
        $this->validate(['body' => 'required|string']);

        $createdMessage = Message::create([
            'body' => $this->body,
            'conversation_id' => $this->selectedConversation->id,
            'sender_id' => auth()->user()->id,
            'receiver_id' => $this->selectedConversation->getReceiver()->id
        ]);

        $this->reset('body');
        $this->loadedMessage->push($createdMessage);

        $this->dispatch("scroll-bottom");

        $this->selectedConversation->updated_at=now();
        $this->selectedConversation->save();

        $this->dispatch("refresh")->to("chat.chat-list");
    }

    public function mount()
    {
        $this->loadedMessage();
    }

    public function render()
    {
        return view('livewire.chat.chat-box');
    }
}
