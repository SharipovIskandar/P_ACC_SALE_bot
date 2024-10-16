<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\TgUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    protected $token = "7931781626:AAFS5F08sF1SJttX8-PqgjNv76GW0kCB3Yg"; // Tokenni o'z tokeningiz bilan almashtiring
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
        session(['awaiting_input' => 'text']);
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
        }

        if ($tgUser->role !== 'admin' && $tgUser->role !== 'super_admin') {
            $this->handleUserCommands($data);


            if (session()->has('awaiting_input')) {
                $awaitingInput = session('awaiting_input');

                if ($awaitingInput === 'text') {
                    $this->sendMessage($chat_id, $data);
                    $this->saveAccountDetails($data);
                }
            }
        }
    }

    public function sendMessage($chat_id, $message)
    {
        Http::post($this->telegramApiUrl . 'sendMessage', [
            'chat_id' => $chat_id,
            'text' => $message,
        ]);
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

            // Input qabul qilish uchun sessiyani saqlang
            session(["awaiting_input" => "text"]);
        }
    }

    public function handleAdminCommands($data)
    {
        $chat_id = $data['message']['chat']['id'];

        $tgUser = TgUser::where('chat_id', $chat_id)->first();
        if ($tgUser && $tgUser->role === 'admin') {
            $unapprovedAccounts = Account::where('is_approved', false)->get();

            if ($unapprovedAccounts->isEmpty()) {
                $this->sendMessage($chat_id, "Hozirda tasdiqlanmagan elonlar yo'q.");
            } else {
                foreach ($unapprovedAccounts as $account) {
                    $this->sendMessage($chat_id, "Elon ID: {$account->id}, Mazmun: {$account->properties}");
                }
            }
        } else {
            $this->sendMessage($chat_id, "Sizda bu amallarni bajarish huquqi yo'q.");
        }
    }

    public function saveAccountDetails($data)
    {
        $chat_id = $data['message']['chat']['id'];
        $inputData = $data['message']['text'] ?? '';

        $tgUser = TgUser::where('chat_id', $chat_id)->first();
        if (!$tgUser) {
            $this->sendMessage($chat_id, "❌ Sizning akk topilmadi. Iltimos, qayta urinib ko'ring.");
            return;
        }

        // Yangi Account yozuvini yaratish
        $account = new Account();
        $account->properties = $inputData;
        $account->tg_user_id = $tgUser->id;
        $account->is_approved = false;
        $account->save();

        // Yangi yozuvning ID sini olish
        $accountId = $account->id;

        // Faylni saqlash
        $this->saveFile($data, $accountId); // Account ID ni argument sifatida o'tkazish

        $this->sendSticker($chat_id, "CAADAgADtgIAAlcI4uIgLpmSpY15DgI"); // Bu yerda sticker ID'sini qo'shing
        $this->sendMessage($chat_id, "✅ Akk sotish uchun qo'yildi, kanalga qo'yilishi uchun protsesga qo'yildi! \n\n📝 Iltimos, izohlaringizni qoldiring yoki boshqa buyurtmalar bilan davom eting!");

        session()->forget('awaiting_input');
    }

    protected function sendSticker($chat_id, $sticker_id)
    {
        $url = "https://api.telegram.org/bot" . "7931781626:AAFS5F08sF1SJttX8-PqgjNv76GW0kCB3Yg" . "/sendSticker";

        $postData = [
            'chat_id' => $chat_id,
            'sticker' => $sticker_id,
        ];

        $this->makeRequest($url, $postData);
    }

    protected function makeRequest($url, $postData)
    {
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->post($url, [
                'form_params' => $postData,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Telegram API xatosi: ' . $e->getMessage());
            return null;
        }
    }

    protected function saveFile($data, $accountId) // accountId ni qo'shish
    {
        $chat_id = $data['message']['chat']['id'];

        if (isset($data['message']['photo']) || isset($data['message']['video'])) {
            if (isset($data['message']['photo'])) {
                $file_id = end($data['message']['photo'])['file_id']; // Eng katta rasmni olish
            } elseif (isset($data['message']['video'])) {
                $file_id = $data['message']['video']['file_id'];
            } else {
                $this->sendMessage($chat_id, "Rasm yoki video topilmadi.");
                return;
            }

            $fileResponse = Http::get($this->telegramApiUrl . 'getFile', [
                'file_id' => $file_id
            ]);

            if ($fileResponse->successful()) {
                $filePath = $fileResponse['result']['file_path'];
                $fileUrl = "https://api.telegram.org/file/bot" . $this->token . "/" . $filePath;

                $fileContent = file_get_contents($fileUrl);
                $fileName = basename($filePath);
                $savePath = public_path("uploads/" . uniqid() . "_" . $fileName);

                // Faylni saqlash
                file_put_contents($savePath, $fileContent);

                // Media faylni Account ga bog'lash
                $account = Account::find($accountId); // ID orqali accountni topish
                if ($account) {
                    $account->media_file_path = $savePath; // Fayl yo'lini saqlash
                    $account->save(); // O'zgartirishlarni saqlash
                }

                $this->sendMessage($chat_id, "✅ Fayl muvaffaqiyatli saqlandi: $fileName");
            } else {
                $this->sendMessage($chat_id, "❌ Faylni olishda xatolik yuz berdi.");
            }
        }
    }

}

