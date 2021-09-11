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

use InvalidArgumentException;
use ShockedPlot7560\FactionMaster\libs\jojoe77777\FormAPI\CustomForm;
use PDO;
use pocketmine\Player;
use ShockedPlot7560\FactionMaster\API\MainAPI;
use ShockedPlot7560\FactionMaster\Database\Entity\FactionEntity;
use ShockedPlot7560\FactionMaster\Database\Entity\UserEntity;
use ShockedPlot7560\FactionMaster\Database\Table\FactionTable;
use ShockedPlot7560\FactionMaster\Route\Route;
use ShockedPlot7560\FactionMaster\Route\RouterFactory;
use ShockedPlot7560\FactionMaster\Utils\Utils;
use ShockedPlot7560\FactionMasterInvitationImprove\Main;

class SelectFaction implements Route {

    const SLUG = "selectFaction";

    public $PermissionNeed = [];
    public $callable;
    public $backMenu;
    /** @var bool */
    private $menuActive = false;
    private $options = [];
    private $UserEntity;

    public function getSlug(): string
    {
        return self::SLUG;
    }

    /**
     * @param Player $player
     * @param array|null $params Give to first item the message to print if wanted
     */
    public function __invoke(Player $player, UserEntity $User, array $UserPermissions, ?array $params = null) {
        $this->UserEntity = $User;
        if (count($params) >= 3) {
            $factionName = $params[0];
            $this->callable = $params[1];
            $this->backMenu = $params[2];
        }else{
            throw new InvalidArgumentException("\$params has not the good format");
        }
        $menu = $this->createSelectMenu($factionName);
        $player->sendForm($menu);
    }

    public function call() : callable{
        $backMenu = $this->backMenu;
        $callable = $this->callable;
        return function (Player $Player, $data) use ($backMenu, $callable) {
            if ($data === null || !isset($backMenu) || !isset($callable)) return;
            if (!$this->menuActive) {
                Utils::processMenu(RouterFactory::get($backMenu), $Player);
                return;
            }
            call_user_func($callable, $this->options[$data[0]]);
        };
    }

    private function createSelectMenu(string $factionName): CustomForm {
        $menu = new CustomForm($this->call());
        $menu->setTitle(Utils::getText($this->UserEntity->name, "SELECT_FACTION_PANEL_TITLE"));
        $this->options = [];
        foreach (MainAPI::$factions as $name => $faction) {
            if (strpos($name, $factionName) !== false && $name !== $this->UserEntity->faction && count($this->options) < Main::getConfigF("limit-selected-faction")) {
                $this->options[] = $faction->name;
            }
        }
        if (count($this->options) != 0) {
            $menu->addDropdown(Utils::getText($this->UserEntity->name, "SELECT_FACTION_PANEL_CONTENT"), $this->options);
            $this->menuActive = true;
        }else{
            $menu->addLabel(Utils::getText($this->UserEntity->name, "SELECT_FACTION_PANEL_ERROR", [
                "needle" => $factionName
            ]));
        }
        return $menu;
    }
}