<?php
namespace DispenserReset;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\math\Vector3;
use pocketmine\tile\Dispenser;
use pocketmine\item\Item;

class Main extends PluginBase implements CommandExecutor, Listener
{
	private static $obj = null;
	public function onEnable()
	{
		if(!self::$obj instanceof Main)
		{
			self::$obj = $this;
		}
        @mkdir($this->getDataFolder());
        $this->iconfig=new Config($this->getDataFolder()."items.yml", Config::YAML, array());
        if(!$this->iconfig->exists("items"))
        {
        	$this->iconfig->set("items",array(262,0,332,0));
        	$this->iconfig->save();
        }
        $this->items=$this->iconfig->get("items");
        
        $this->config=new Config($this->getDataFolder()."dispenser.yml", Config::YAML, array());
        if(!$this->config->exists("dispenser"))
        {
        	$this->config->set("dispenser",array());
        	$this->config->save();
        }
        $this->dispenser=$this->config->get("dispenser");
        $this->set=array();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args)
    {
    	if(!isset($args[0])){unset($sender,$cmd,$label,$args);return false;};
    	switch($args[0])
    	{
    	case "reload":
    		unset($this->iconfig,$this->config);
    		@mkdir($this->getDataFolder());
        	$this->iconfig=new Config($this->getDataFolder()."items.yml", Config::YAML, array());
        	if(!$this->iconfig->exists("items"))
        	{
        		$this->iconfig->set("items",array(262,0,332,0));
        		$this->iconfig->save();
        	}
        	$this->items=$this->iconfig->get("items");
        	
        	$this->config=new Config($this->getDataFolder()."dispenser.yml", Config::YAML, array());
        	if(!$this->config->exists("dispenser"))
        	{
        		$this->config->set("dispenser",array());
        		$this->config->save();
        	}
        	$this->dispenser=$this->config->get("dispenser");
        	$this->set=array();
    		$sender->sendMessage("[DispenserReset] Reload successful");
    		break;
    	case "reset":
    		$this->Resetdispenser();
    		$sender->sendMessage("[DispenserReset] Item has been reset");
    		break;
    	case "clear":
    		$this->Cleardispenser();
    		$sender->sendMessage("[DispenserReset] dispensers has been clear");
    		break;
    	case "add":
    	case "remove":
    		if(!$sender instanceof Player){$sender->sendMessage("[DispenserReset] 请在游戏内使用这个指令");break;};
    		$this->set[$sender->getName()] = $args[0];
            $sender->sendMessage("[DispenserReset] Click the dispenser to confirm");
    		break;
    	default:
    		unset($sender,$cmd,$label,$args);
			return false;
			break;
    	}
        unset($sender,$cmd,$label,$args);
        return true;
    }
    
    public static function getInstance()
	{
		return self::$obj;
	}
    
    public function onInteract(PlayerInteractEvent $event)
    {
    	$block=$event->getBlock();
        if(isset($this->set[$event->getPlayer()->getName()]))
        {
        	if($block->getId()!=23)
        	{
        		$event->getPlayer()->sendMessage("[DispenserReset] Please tap a dispenser");
            	unset($event,$block,$key,$val);
            	return;
        	}
        	$a=$this->set[$event->getPlayer()->getName()];
        	unset($this->set[$event->getPlayer()->getName()]);
            switch($a)
            {
            case "add":
            	foreach($this->dispenser as $key=>$val)
            	{
            		if($val["x"]==$block->getX() && $val["y"]==$block->getY() && $val["z"]==$block->getZ() && $val["level"]==$block->getLevel()->getFolderName())
            		{
            			$event->getPlayer()->sendMessage("[DispenserReset] This dispenser was in reset list");
            			unset($event,$block,$key,$val);
            			return;
            		}
            	}
            	$tmp=array();
            	$tmp["x"]=$block->getX();
            	$tmp["y"]=$block->getY();
            	$tmp["z"]=$block->getZ();
            	$tmp["level"]=$block->getLevel()->getFolderName();
                $this->dispenser[]=$tmp;
                unset($tmp,$key,$val);
                $event->getPlayer()->sendMessage("[DispenserReset] Add to reset list....");
                break;
            case "remove":
            	$msg="[DispenserReset] This dispenser isn't in the reset list";
                foreach($this->dispenser as $key=>$val)
            	{
            		if($val["x"]==$block->getX() && $val["y"]==$block->getY() && $val["z"]==$block->getZ() && $val["level"]==$block->getLevel()->getFolderName())
            		{
            			array_splice($this->dispenser,$key,1);
            			$msg="[DispenserReset] Remove from reset list...";
            			break;
            		}
            	}
            	$event->getPlayer()->sendMessage($msg);
            	unset($key,$val);
                break;
            }
            
        }
        $this->savedispenser();
        unset($block,$event,$a);
    }
    public function onBreakEvent(BlockBreakEvent $event)
    {
    	$block=$event->getBlock();
    	foreach($this->dispenser as $key=>$val)
        {
        	if($val["x"]==$block->getX() && $val["y"]==$block->getY() && $val["z"]==$block->getZ() && $val["level"]==$block->getLevel()->getFolderName())
        	{
        		if(!$event->getPlayer()->isOp())
        		{
        			$event->getPlayer()->sendMessage("[DispenserReset] You can't break this dispenser");
        			$event->setCancelled();
        			break;
        		}
            	array_splice($this->dispenser,$key,1);
            	$event->getPlayer()->sendMessage("[DispenserReset] Remove from reset list...");
            	break;
            }
        }
        unset($event,$block,$key,$val);
	}
	
    public function onDisable()
    {
        //$this->savedispenser();
    }
    
    public function Cleardispenser()
    {
    	foreach($this->dispenser as $val)
    	{
    		if(!isset($val["level"])){continue;};
    		$level=$this->getServer()->getLevelByName($val["level"]);
    		if(!$level instanceof Level){continue;};
    		$v3=new Vector3($val["x"],$val["y"],$val["z"]);
    		if($level->getBlock($v3)->getId()!=23){continue;};
    		$dispenser=$level->getTile($v3);
    		for($i=0;$i<$dispenser->getSize();$i++)
    		{
    			$dispenser->getInventory()->setItem($i,Item::get(0,0));
    		}
    	}
    	unset($val,$level,$v3,$dispenser,$i,$rand,$rid,$item);
    }
    
    public function Resetdispenser()
    {
    	foreach($this->dispenser as $val)
    	{
    		if(!isset($val["level"])){continue;};
    		$level=$this->getServer()->getLevelByName($val["level"]);
    		if(!$level instanceof Level){continue;};
    		$v3=new Vector3($val["x"],$val["y"],$val["z"]);
    		if($level->getBlock($v3)->getId()!=23){continue;};
    		$dispenser=$level->getTile($v3);
    		for($i=0;$i<$dispenser->getSize();$i++)
    		{
    			$dispenser->getInventory()->setItem($i,Item::get(0,0));
    		}
    		$rand=mt_rand(5,10);
    		for($i=0;$i<$rand;$i++)
    		{
    			$rid=mt_rand(0,count($this->items)/2);
    			$item=Item::get((int)$this->items[$rid],(int)$this->items[$rid+1]);
    			$rid=mt_rand(0,$dispenser->getSize()-1);
    			while($dispenser->getInventory()->getItem($rid)->getId()!=0)
    			{
    				$rid=mt_rand(0,$dispenser->getSize()-1);
    			}
    			$dispenser->getInventory()->setItem($rid,$item);
    		}
    	}
    	unset($val,$level,$v3,$dispenser,$i,$rand,$rid,$item);
    }
    public function savedispenser()
    {
    	$this->config->set("dispenser",$this->dispenser);
    	$this->config->save();
    }
}
