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
use PDO;
use pocketmine\Player;
use ShockedPlot7560\FactionMaster\API\MainAPI;
use ShockedPlot7560\FactionMaster\Database\Entity\UserEntity;
use ShockedPlot7560\FactionMaster\Database\Table\UserTable;
use ShockedPlot7560\FactionMaster\Route\Route;
use ShockedPlot7560\FactionMaster\Route\RouterFactory;
use ShockedPlot7560\FactionMaster\Utils\Utils;
use ShockedPlot7560\FactionMasterInvitationImprove\Main as FactionMasterInvitationImproveMain;

class SelectPlayer implements Route {

    const SLUG = "selectPlayer";

    public $PermissionNeed = [];
    public $callable;
    public $backMenu;
    /** @var bool */
    private $menuActive = false;
    private $options = [];

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
        if (isset($params[0]) && \is_string($params[0])) $playerName = $params[0];
        if (isset($params[1]) && is_callable($params[1])) $this->callable = $params[1];
        if (isset($params[2]) && \is_string($params[2])) $this->backMenu = $params[2];
        if (isset($params[0]) && $params[0] == "") return Utils::processMenu(RouterFactory::get($this->backMenu), $player);
        $menu = $this->createSelectMenu($playerName);
        $player->sendForm($menu);
    }

    public function call() : callable{
        $backMenu = $this->backMenu;
        $callable = $this->callable;
        return function (Player $Player, $data) use ($backMenu, $callable) {
            if ($data === null || !isset($backMenu) || !isset($callable)) return;
            if (!$this->menuActive) return Utils::processMenu(RouterFactory::get($backMenu), $Player);
            call_user_func($callable, $this->options[$data[0]]);
        };
    }

    private function createSelectMenu(string $playerName): CustomForm {
        $menu = new CustomForm($this->call());
        $menu->setTitle(Utils::getText($this->UserEntity->name, "SELECT_PLAYER_PANEL_TITLE"));
        $query = MainAPI::$PDO->prepare("SELECT * FROM " . UserTable::TABLE_NAME . " WHERE INSTR(name, :needle) > 0 AND name != :playerName LIMIT " . FactionMasterInvitationImproveMain::getConfigF("limit-selected-player"));
        $query->execute([
            "needle" => $playerName,
            "playerName" => $this->UserEntity->name
        ]);
        $this->options = [];
        foreach ($query->fetchAll(PDO::FETCH_CLASS, UserEntity::class) as $user) {
            $this->options[] = $user->name;
        }
        if (MainAPI::getUser($playerName) instanceof UserEntity) $this->options[] = $playerName;
        if (count($this->options) != 0) {
            $menu->addDropdown(Utils::getText($this->UserEntity->name, "SELECT_PLAYER_PANEL_CONTENT"), $this->options);
            $this->menuActive = true;
        }else{
            $menu->addLabel(Utils::getText($this->UserEntity->name, "SELECT_PLAYER_PANEL_ERROR", [
                "needle" => $playerName
            ]));
        }
        return $menu;
    }
}