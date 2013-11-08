<?php

/*
__PocketMine Plugin__
name=TeamPVP
description=Easy to use kits? Hmmm
version=0.1
author=Junyi00
class=TeamPVP
apiversion=10
*/

class TeamPVP implements Plugin{
	private $api, $path, $config;
	private $minimumMem;
	private $blockID;
	private $gameStarted;
	
	private $Team1count, $Team2count;
	private $Team1mem, $Team2mem;
	
	private $gameMem;
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}
	
	public function init(){
		$this->path = $this->api->plugin->configPath($this);
		$this->config = new Config($this->path."config.yml", CONFIG_YAML, array(
			"Game Rules" => array(
				"Minimum members on both team" => 4,
				"Wall block (ID)" => 5,
				"Game Started" => false),
			"Team1" => array(
				"x" => 0,
				"y" => 0,
				"z" => 0,
				"members count" => 0,
				"members" => ""),
			"Team2" => array(
				"x" => 0,
				"y" => 0,
				"z" => 0,
				"members count" => 0,
				"members" => ""),
			"Prize Room" => array(
				"x" => 0,
				"y" => 0,
				"z" => 0)
			));
		$this->initVAR();
		$this->api->addHandler("player.block.place", array($this, "BreakPlace"));
		$this->api->addHandler("player.block.break", array($this, "BreakPlace"));
		$this->api->addHandler("player.block.touch", array($this, "SignTP"));
		$this->api->addHandler("player.spawn", array($this, "ChangeGamemode"));
		$this->api->addHandler("player.quit", array($this, "PlayerQuit"));
		$this->api->addHandler("entity.health.change", array($this, "PreventPVP"));
		$this->api->addHandler("player.death", array($this, "GameStuff"));
		$this->api->schedule(1200* 1, array($this, "Check"), array(), true);
	}
	
	public function __destruct() {}
	
	public function initVAR() {
		$cfg = $this->api->plugin->readYAML($this->path . "config.yml");
		$this->memCount = 0;
		$this->blockID = (int) $cfg["Game Rules"]["Wall block (ID)"];
		$this->MinimumMem = (int) $cfg["Game Rules"]["Minimum members on both team"];
		$this->gameStarted = false;
	}
	
	public function resetGame($type, $teamNum) {
		switch($teamNum) {
			case 1:
				$t1 = explode(",", $this->Team1mem);
				for($i=0;$i<count($t1);$i++) {
					$p = $this->api->player->get($t1[$i]);
					if ($type == "leave") {
						$p->sendChat("Someone has left the game!");
					}
					else {
						$p->sendChat("Game has ended");
					}
					$p->teleport($p->level->getSpawn());
				}
				break;
			case 2:
				$t2 = explode(",", $this->Team2mem);
				for($i=0;$i<count($t2);$i++) {
					$p = $this->api->player->get($t2[$i]);
					if ($type == "leave") {
						$p->sendChat("Someone has left the game!");
					}
					else {
						$p->sendChat("You team lost");
					}
					$p->teleport($p->level->getSpawn());
				}
				break;
		}
		
		$this->Team1count = 0; 
		$this->Team2count = 0;
		$this->Team1mem = "";
		$this->Team2mem = "";	
	}
	
	public function initConfig() {
		$cfg = $this->api->plugin->readYAML($this->path . "config.yml");
		$cfg["Game Rules"]["Wall block (ID)"] = $this->blockID;
		$cfg["Game Rules"]["Game Started"] = $this->gameStarted;
		$cfg["Team1"]["members count"] = $this->Team1count;
		$cfg["Team2"]["members count"] = $this->Team2count;
		$cfg["Team1"]["members"] = $this->Team1mem;
		$cfg["Team2"]["members"] = $this->Team2mem;
		$this->overwriteConfig($cfg);
	}
	
	public function PlayertoTeamLobby($player, $teamNum) {
		$cfg = $this->api->plugin->readYAML($this->path . "config.yml");
		switch($teamNum) {
			case 1:
				$pos = new Vector3((int)$cfg["Team1"]['x'], (int)$cfg["Team1"]['y'], (int)$cfg["Team1"]['z']);
				$player->teleport($pos);
				$this->Team1count++;
				$this->Team1mem = $this->Team1mem.$player->username.",";
				break;
			case 2:
				$pos = new Vector3((int)$cfg["Team2"]['x'], (int)$cfg["Team2"]['y'], (int)$cfg["Team2"]['z']);
				$player->teleport($pos);
				$this->Team2count++;
				$this->Team2mem = $this->Team2mem.$player->username.",";
				break;
		}
		$this->initConfig();
	}
	
	public function GameStuff() {
		if (strpos($Team1mem, $data['player']->username.",") !== false) {
			$this->Team1count--;
			if($this->Team1count <= 0) {
				$this->resetGame("End", 1);
				
				$prizeroom = new Vector3((int)$data["Prize Room"]['x'], (int)$data["Prize Room"]['y'], (int)$data["Prize Room"]['z']);
				$t2 = explode(",", $this->Team2mem);
				for($i=0;$i<count($t2);$i++) {
					$p = $this->api->player->get($t2[$i]);
					$p->sendChat("Your team won!");
					$p->teleport($prizeroom);
				}
				
				$this->api->chat->broadcast("Team 2 won the game!!!");
			}
			else {
				$this->api->chat->broadcast("1 player from Team1 has been down!");
				$this->api->chat->broadcast("Team1: ".$this->Team1count."/".$gameMem." - Team2: ".$this->Team2count."/".$gameMem);
			}
		}
		elseif (strpos($Team2mem, $data['player']->username.",") !== false) {
			$this->Team2count--;
			if($this->Team2count <= 0) {
				$this->resetGame("End", 2);
				
				$prizeroom = new Vector3((int)$data["Prize Room"]['x'], (int)$data["Prize Room"]['y'], (int)$data["Prize Room"]['z']);
				$t1 = explode(",", $this->Team1mem);
				for($i=0;$i<count($t1);$i++) {
					$p = $this->api->player->get($t2[$i]);
					$p->sendChat("Your team won!");
					$p->teleport($prizeroom);
				}
				
				$this->api->chat->broadcast("Team 1 won the game!!!");
			}
			else {
				$this->api->chat->broadcast("1 player from Team1 has been down!");
				$this->api->chat->broadcast("Team1: ".$this->Team1count."/".$gameMem." - Team2: ".$this->Team2count."/".$gameMem);
			}

		}

	}
	
	public function Check() {
		if ($this->gameStarted == true) {
			return;
		}
		else {
			if ($this->Team1count == $this->Team2count && $this->Team1count >= $this->MinimumMem) {
				$this->gameStarted = true;
				$this->gameMem = $this->Team1count;
				$players = $this->api->player->online();
				$this->api->chat->broadcast("Game has started!!!!");
			}
		}
	}
	
	public function BreakPlace($data, $event) {
		switch($event) {
			case "player.block.place":
				return false;
			case "player.block.break":
				if ($this->gameStarted == true) {
					if ($data['target']->getID() == $this->blockID) {
						return true;
					}
					else {
						return false;
					}
				}
				else {
					return false;
				}
		}
	}
	
	public function PreventPVP($data, $event) {
		if (!$data['entity']->class === ENTITY_PLAYER) {
			return true;
		}
		$player = $this->api->player->getByEID($data['entity']->eid);
		$username = $player->username;
		
		if ($this->gameStarted == false) {
			return false;
		}
		else {
			if (strpos($Team1mem, $username.",") !== false || strpos($Team2mem, $username.",") !== false) {
				return true;
			}
			else {
				return false;
			}
		}
	}
	
	public function ChangeGamemode($data, $event) {
		$p = $data['player'];
		if (!$p->getGamemode() == "survival") {
			$p->setGamemode(0);
		}
	}
	
	public function PlayerQuit($data, $event) {
		if (in_array($data['player']->username, explode(",", $this->Team1mem))) {
			$this->Team1count--;
			$this->Team1mem = str_replace($data['player'].",", "", $this->Team1mem);
			if ($this->gameStarted == true) {
				$this->gameStarted = false;
				$this->resetGame();
			}
		}
		elseif (in_array($data['player']->username, explode(",", $this->Team2mem))) {
			$this->Team2count--;
			$this->Team2mem = str_replace($data['player'].",", "", $this->Team1mem);
			if ($this->gameStarted == true) {
				$this->gameStarted = false;
				$this->resetGame("leave", 0);
				$this->resetGame("leave", 1);
			}

		}
		$this->initConfig();
	}
	
	public function SignTP($data, $event) {
		switch($data['type']) {
			case "place":
				$player = $data["player"];
				$position = new Position ($data["target"], false, false, $data["target"]->level);
				$sign = $this->api->tile->get($position);
				
 				if (($sign instanceof Tile) && $sign->class === TILE_SIGN){
 					if ($sign->data['Text1'] == "[TP]" && $sign->data["Text2"] == "Team Lobby") {
 						if ($this->gameStarted == true) {
 							$player->sendChat("The game has already started, plase wait for it to end!");
 							return;
 						}
 						else {
 							if ($this->Team1count < $this->Team2count) {
 								$this->PlayertoTeamLobby($player, 1);
 							}
 							elseif ($this->Team2count < $this->Team1count) {
 								$this->PlayertoTeamLobby($player, 2);
 							}

 						}
 					}
 				}
 				elseif ($sign->data['Text1'] == "[TP]" && $sign->data["Text2"] == "Main Lobby") {
 					$player->teleport($player->level->getSpawn());
 				}
		}
	}
	
	private function overwriteConfig($dat){
		$cfg = array();
		$cfg = $this->api->plugin->readYAML($this->path . "config.yml");
		$result = array_merge($cfg, $dat);
		$this->api->plugin->writeYAML($this->path."config.yml", $result);
	}
	
}
