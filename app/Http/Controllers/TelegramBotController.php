<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\TgUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class TelegramBotController extends Controller
{
    protected $token = '7931781626:AAFS5F08sF1SJttX8-PqgjNv76GW0kCB3Yg';
    protected $telegramApiUrl;

    public function __construct()
    {
        $this->telegramApiUrl = 'https://api.telegram.org/bot' . $this->token . '/';
    }

    public function handleWebhook(Request $request)
    {
        $data = $request->all();
        $chat_id = $data['message']['chat']['id'] ?? null;
        $name = $data['message']['from']['first_name'] ?? 'Foydalanuvchi';
        $awaitingInput = session(["awaiting_input" => "text"]);
        if (!$chat_id) {
            return response()->json(['error' => 'Chat ID not found.'], 400);
        }

        $tgUser = TgUser::firstOrCreate(
            ['chat_id' => $chat_id],
            ['username' => $name]
        );

        if ($tgUser->role === 'admin') {
            $this->handleAdminCommands($data);
        } elseif ($tgUser->role === 'super_admin') {
            $this->handleSuperAdminCommands($data);
        } else {
            $this->handleUserCommands($data);
            if($awaitingInput){
                $this->saveAccountDetails($data);
            }
        }
    }

    protected function handleSuperAdminCommands($data)
    {
        if (isset($data['message']['text'])) {
            if (preg_match('/\/list_users/', $data['message']['text'])) {
                $this->listUsers($data['message']['chat']['id']);
            }
        }
    }

    protected function listUsers($chat_id)
    {
        $users = TgUser::all(); // Barcha foydalanuvchilarni olish
        $userList = "Foydalanuvchilar ro'yxati:\n";

        foreach ($users as $user) {
            $userList .= "ID: {$user->chat_id}, Ism: {$user->username}, Rol: {$user->role}\n";
        }

        $this->sendMessage($chat_id, $userList);
    }

    protected function handleUserCommands($data)
    {
        if (isset($data['message']['text'])) {
            $this->sendMessage($data['message']['chat']['id'],
                "🌟 Salom, aziz foydalanuvchi! Sizdan anketani to'ldirishni so'raymiz. Iltimos, hisobingiz uchun quyidagi ma'lumotlarni aniq va to'liq formatda yuboring:\n\n💰 NARXI:  ✅\n1️⃣ PUBG username: \n2️⃣ PUBG ID: \n3️⃣ UC (agar mavjud bo'lsa): \n4️⃣ RP (agar mavjud bo'lsa): \n5️⃣ Qo'shimcha material yoki ma'lumotlar (agar mavjud bo'lsa): \n\n📸 Rasm yoki sticker yuborishni unutmaslik uchun tayyor bo'ling! Sizning ma'lumotlaringiz biz uchun juda muhim. Rahmat! 🎉");
        }
    }

    protected function handleAdminCommands($data)
    {
        if (isset($data['message']['text'])) {
            if (preg_match('/\/approve_account (\d+)/', $data['message']['text'], $matches)) {
                $accountId = $matches[1];
                // account approval logic here
            }
        }
    }

    protected function sendMessage($chat_id, $message)
    {
        Http::post($this->telegramApiUrl . 'sendMessage', [
            'chat_id' => $chat_id,
            'text' => $message,
        ]);
    }
    public function saveAccountDetails($data)
    {
        $chat_id = $data['message']['chat']['id'];

        // Foydalanuvchidan kelgan anketani to'liq tekst sifatida olish
        $inputData = $data['message']['text'] ?? ''; // Bu yerda to'liq tekstni olish

        $account = new Account();
        $account->properties = $inputData; // Butun tekst sifatida saqlash
        $account->tg_user_id = $chat_id;
        $account->is_approved = false;
        $account->save();
    }



}
