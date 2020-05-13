<?php

namespace xtakumatutix\wrapping;

use pocketmine\nbt\tag\IntTag;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\event\Listener;
use pocketmine\event\Player\PlayerInteractEvent;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;

Class Main extends PluginBase implements Listener {

    public function onEnable()
    {
        $this->getLogger()->notice("読み込み完了_ver.1.0.0");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if ($sender instanceof Player) {
            if($sender->getInventory()->all(Item::get(Item::PAPER))) {
                $handitem = $sender->getInventory()->getItemInHand();
                $id = $handitem->getID();
                $itemname = $handitem->getName();
                $damage = $handitem->getDamage();
                $count = $handitem->getCount();

                $sender->getInventory()->removeItem(Item::get($id,$damage,$count));
                $sender->getInventory()->removeItem(Item::get(339,0,1));

                $name = $sender->getName();
                $item = Item::get(378, 0);
                $item->setLore(["中身はなにかな...?"]);
                $item->setCustomName("{$name}様より");

                //アイテムに付与するタグを生成
                $pluginName = $this->getName();//プラグイン名を取得
                $compoundTag = new CompoundTag($pluginName, [
                    new IntTag("item-id", $id),//アイテムID
                    new IntTag("item-damage", $damage),//アイテムダメージ
                    new IntTag("item-count", $count),//アイテム個数
                    new StringTag("sender-name", $name),//送り主名
                ]);

                //アイテムにNBTを設定する
                $item->setNamedTag($compoundTag);

                $sender->getInventory()->addItem($item);
                $sender->sendMessage("§a >> ラッピングしました！！");

                return true;
            }else{
                $sender->sendMessage("§c >> 紙がありません");
                return true;
            }
        }else{
            $sender->sendMessage("ゲーム内で使用してください");
            return true;
        }
    }

    public function tap(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $inventory = $player->getInventory();//インベントリを代入
        $handItem = $inventory->getItemInHand();//手持ちアイテムを代入
        if($handItem->getId() === 378){
            $pluginName = $this->getName();//プラグイン名を取得
            $compoundTag = $handItem->getNamedTag()->getCompoundTag($pluginName);//CompoundTagを取得
            if($compoundTag === null){
                //CompoundTagが設定されていなかった場合
                return;//ここで処理を終了
            }
            //中身のItemオブジェクトを生成
            $itemContent = new Item(
                $compoundTag->getInt("item-id"),//アイテムID
                $compoundTag->getInt("item-damage"),//アイテムダメージ
                $compoundTag->getInt("item-count")//アイテム個数
            );
            //送り主を取得
            $senderName = $compoundTag->getString("sender-name");
            //ラッピングのアイテムを消す
            $inventory->removeItem(Item::get(378));
            //中身を追加する
            $inventory->addItem($itemContent);
            //メッセージ送信
            $player->sendMessage("§a ?? {$senderName}様からのプレゼントです！");
            //サウンド再生
            $pk = new PlaySoundPacket();
            $pk->soundName = 'random.levelup';
            $pk->x = $player->x;
            $pk->y = $player->y;
            $pk->z = $player->z;
            $pk->volume = 1;
            $pk->pitch = 1;
            $player->dataPacket($pk);
        }
    }
}