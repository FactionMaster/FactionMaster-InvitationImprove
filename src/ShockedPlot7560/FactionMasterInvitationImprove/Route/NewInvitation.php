<?php

/*
 *
 *      ______           __  _                __  ___           __
 *     / ____/___ ______/ /_(_)___  ____     /  |/  /___ ______/ /____  _____
 *    / /_  / __ `/ ___/ __/ / __ \/ __ \   / /|_/ / __ `/ ___/ __/ _ \/ ___/
 *   / __/ / /_/ / /__/ /_/ / /_/ / / / /  / /  / / /_/ (__  ) /_/  __/ /  
 *  /_/    \__,_/\___/\__/_/\____/_/ /_/  /_/  /_/\__,_/____/\__/\___/_/ 
 *
 * FactionMaster - A Faction plugin for PocketMine-MP
 * This file is part of FactionMaster and is an extension
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @author ShockedPlot7560 
 * @link https://github.com/ShockedPlot7560
 * 
 *
*/

namespace ShockedPlot7560\FactionMasterInvitationImprove\Route;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use ShockedPlot7560\FactionMaster\API\MainAPI;
use ShockedPlot7560\FactionMaster\Database\Entity\FactionEntity;
use ShockedPlot7560\FactionMaster\Event\InvitationSendEvent;
use ShockedPlot7560\FactionMaster\Route\NewInvitation as RouteNewInvitation;
use ShockedPlot7560\FactionMaster\Route\Route;
use ShockedPlot7560\FactionMaster\Route\RouterFactory;
use ShockedPlot7560\FactionMaster\Utils\Utils;

class NewInvitation extends RouteNewInvitation implements Route {

    public $backMenu;

    public function getSlug(): string {
        return self::SLUG;
    }

    public function __construct() {
        parent::__construct();
    }

    public function call(): callable {
        $backMenu = $this->backMenu->getSlug();
        return function (Player $Player, $data) use ($backMenu) {
            if ($data === null) return;

            return Utils::processMenu(RouterFactory::get(SelectFaction::SLUG), $Player, [
                $data[1],
                function (string $factionName) use ($Player, $backMenu) {
                    $FactionRequest = MainAPI::getFaction($factionName);
                    if ($FactionRequest instanceof FactionEntity) {
                        if (count($FactionRequest->members) < $FactionRequest->max_player) {
                            if (!MainAPI::getFactionOfPlayer($Player->getName()) instanceof FactionEntity) {
                                if (!MainAPI::areInInvitation($Player->getName(), $factionName, InvitationSendEvent::MEMBER_TYPE)) {
                                    if (MainAPI::makeInvitation($Player->getName(), $factionName, InvitationSendEvent::MEMBER_TYPE)) {
                                        (new InvitationSendEvent($Player, $Player->getName(), $factionName, InvitationSendEvent::MEMBER_TYPE))->call();
                                        Utils::processMenu(RouterFactory::get($backMenu), $Player, [Utils::getText($this->UserEntity->name, "SUCCESS_SEND_INVITATION", ['name' => $factionName])] );
                                    }else{
                                        $menu = $this->createInvitationMenu(Utils::getText($this->UserEntity->name, "ERROR"));
                                        $Player->sendForm($menu);;
                                    }
                                }else{
                                    $menu = $this->createInvitationMenu(Utils::getText($this->UserEntity->name, "ALREADY_PENDING_INVITATION"));
                                    $Player->sendForm($menu);;
                                }
                            }else{
                                $menu = $this->createInvitationMenu(Utils::getText($this->UserEntity->name, "ALREADY_IN_THIS_FACTION"));
                                $Player->sendForm($menu);;
                            }
                        }else{
                            $message = Utils::getText($this->UserEntity->name, "MAX_PLAYER_REACH");
                            Utils::processMenu($backMenu, $Player, [$message] );
                        }
                        
                    }else{
                        $menu = $this->createInvitationMenu(Utils::getText($this->UserEntity->name, "FACTION_DONT_EXIST"));
                        $Player->sendForm($menu);;
                    }   
                },
                $backMenu
            ]);
        };
    }

    private function createInvitationMenu(string $message = ""): CustomForm {
        $menu = new CustomForm($this->call());
        $menu->setTitle(Utils::getText($this->UserEntity->name, "SEND_INVITATION_PANEL_TITLE"));
        $menu->addLabel(Utils::getText($this->UserEntity->name, "SEND_INVITATION_PANEL_CONTENT") . "\n" . $message);
        $menu->addInput(Utils::getText($this->UserEntity->name, "SEND_INVITATION_PANEL_INPUT_CONTENT_FACTION"));
        return $menu;
    }
}