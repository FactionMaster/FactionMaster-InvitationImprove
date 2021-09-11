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

use ShockedPlot7560\FactionMaster\libs\jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use ShockedPlot7560\FactionMaster\API\MainAPI;
use ShockedPlot7560\FactionMaster\Database\Entity\FactionEntity;
use ShockedPlot7560\FactionMaster\Database\Entity\UserEntity;
use ShockedPlot7560\FactionMaster\Event\FactionJoinEvent;
use ShockedPlot7560\FactionMaster\Event\InvitationAcceptEvent;
use ShockedPlot7560\FactionMaster\Event\InvitationSendEvent;
use ShockedPlot7560\FactionMaster\Route\NewMemberInvitation as RouteNewMemberInvitation;
use ShockedPlot7560\FactionMaster\Route\Route;
use ShockedPlot7560\FactionMaster\Route\RouterFactory;
use ShockedPlot7560\FactionMaster\Task\MenuSendTask;
use ShockedPlot7560\FactionMaster\Utils\Utils;

class NewMemberInvitation extends RouteNewMemberInvitation implements Route {

    public $backMenu;
    private $user;
    
    public function __invoke(Player $player, UserEntity $User, array $UserPermissions, ?array $params = null) {
        $this->user = $User;
        parent::__invoke($player, $User, $UserPermissions, $params);
    }

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

            Utils::processMenu(RouterFactory::get(SelectPlayer::SLUG), $Player, [
                $data[1],
                function (string $playerName) use ($Player, $backMenu) {
                    $targetName = $playerName;
                    $UserRequest = MainAPI::getUser($playerName);
                    $FactionPlayer = MainAPI::getFactionOfPlayer($Player->getName());
                    if (count($FactionPlayer->members) >= $FactionPlayer->max_player) {
                        $menu = $this->createInvitationMenu(Utils::getText($this->user->name, "MAX_PLAYER_REACH"));
                        $Player->sendForm($menu);;
                        return;
                    }
                    if ($UserRequest instanceof UserEntity) {
                        if (!MainAPI::getFactionOfPlayer($playerName) instanceof FactionEntity) {
                            $Faction = $FactionPlayer;
                            if (!MainAPI::areInInvitation($Faction->name, $targetName, InvitationSendEvent::MEMBER_TYPE)) {
                                if (MainAPI::areInInvitation($targetName, $Faction->name, InvitationSendEvent::MEMBER_TYPE)) {
                                    MainAPI::addMember($Faction->name, $UserRequest->name);
                                    Utils::newMenuSendTask(new MenuSendTask(
                                        function () use ($UserRequest, $Faction) {
                                            return MainAPI::getUser($UserRequest->name)->faction === $Faction->name;
                                        },
                                        function () use ($UserRequest, $Player, $Faction, $backMenu) {
                                            (new FactionJoinEvent($UserRequest, $Faction))->call();
                                            $Request = MainAPI::$invitation[$UserRequest->name . "|" . $Faction->name];
                                            MainAPI::removeInvitation($UserRequest->name, $Faction->name, "member");
                                            Utils::newMenuSendTask(new MenuSendTask(
                                                function () use ($UserRequest, $Faction) {
                                                    return !MainAPI::areInInvitation($UserRequest->name, $Faction->name, "member");
                                                },
                                                function () use ($Request, $Player, $backMenu, $UserRequest) {
                                                    (new InvitationAcceptEvent($Player, $Request))->call();
                                                    Utils::processMenu(RouterFactory::get($backMenu), $Player, [Utils::getText($Player->getName(), "SUCCESS_ACCEPT_REQUEST", ['name' => $UserRequest->name])] );
                                                },
                                                function () use ($Player) {
                                                    Utils::processMenu(RouterFactory::get(self::SLUG), $Player, [Utils::getText($Player->getName(), "ERROR")]);
                                                }
                                            ));
                                        },
                                        function () use ($Player) {
                                            Utils::processMenu(RouterFactory::get(self::SLUG), $Player, [Utils::getText($Player->getName(), "ERROR")]);
                                        }
                                    ));
                                }else{
                                    MainAPI::makeInvitation($Faction->name, $targetName, InvitationSendEvent::MEMBER_TYPE);
                                    Utils::newMenuSendTask(new MenuSendTask(
                                        function () use ($Faction, $targetName) {
                                            return MainAPI::areInInvitation($Faction->name, $targetName, InvitationSendEvent::MEMBER_TYPE);
                                        },
                                        function () use ($Player, $targetName, $backMenu, $Faction) {
                                            (new InvitationSendEvent($Player, $Faction->name, $targetName, InvitationSendEvent::MEMBER_TYPE))->call();
                                            Utils::processMenu(RouterFactory::get($backMenu), $Player, [Utils::getText($Player->getName(), "SUCCESS_SEND_INVITATION", ['name' => $targetName])] );
                                        },
                                        function () use ($Player) {
                                            Utils::processMenu(RouterFactory::get(self::SLUG), $Player, [Utils::getText($Player->getName(), "ERROR")]);
                                        }
                                    ));
                                }
                            }else{
                                $menu = $this->createInvitationMenu(Utils::getText($this->user->name, "ALREADY_PENDING_INVITATION"));
                                $Player->sendForm($menu);;
                            }
                        }else{
                            $menu = $this->createInvitationMenu(Utils::getText($this->user->name, "PLAYER_HAVE_ALREADY_FACTION"));
                            $Player->sendForm($menu);;
                        }
                    }else{
                        $menu = $this->createInvitationMenu(Utils::getText($this->user->name, "USER_DONT_EXIST"));
                        $Player->sendForm($menu);;
                    }  
                },
                $backMenu
            ]);
            return;
        };
    }

    private function createInvitationMenu(string $message = ""): CustomForm {
        $menu = new CustomForm($this->call());
        $menu->setTitle(Utils::getText($this->user->name, "SEND_INVITATION_PANEL_TITLE"));
        $menu->addLabel(Utils::getText($this->user->name, "SEND_INVITATION_PANEL_CONTENT") . "\n" . $message);
        $menu->addInput(Utils::getText($this->user->name, "SEND_INVITATION_PANEL_INPUT_CONTENT"));
        return $menu;
    }
}