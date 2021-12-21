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
use pocketmine\Player;
use ShockedPlot7560\FactionMaster\API\MainAPI;
use ShockedPlot7560\FactionMaster\Database\Entity\UserEntity;
use ShockedPlot7560\FactionMaster\libs\jojoe77777\FormAPI\CustomForm;
use ShockedPlot7560\FactionMaster\Route\Route;
use ShockedPlot7560\FactionMaster\Route\RouteBase;
use ShockedPlot7560\FactionMaster\Utils\Utils;
use ShockedPlot7560\FactionMasterInvitationImprove\FactionMasterInvitationImprove;
use function call_user_func;
use function count;
use function strpos;

class SelectPlayer extends RouteBase implements Route {
	const SLUG = "selectPlayer";

	/** @var bool */
	private $menuActive = false;
	private $options = [];

	public function getSlug(): string {
		return self::SLUG;
	}

	public function getPermissions(): array {
		return [];
	}

	public function getBackRoute(): ?Route {
		return $this->getParams()[2];
	}

	protected function getCallable(): callable {
		return $this->getParams()[1];
	}

	protected function getOptions(): array {
		return $this->options;
	}

	public function __invoke(Player $player, UserEntity $userEntity, array $userPermissions, ?array $params = null) {
		$this->init($player, $userEntity, $userPermissions, $params);
		if (count($params) >= 3) {
			$playerName = $params[0];
		} else {
			throw new InvalidArgumentException("\$params has not the good format");
		}
		if (isset($params[0]) && $params[0] == "") {
			Utils::processMenu($this->getBackRoute(), $player);
			return;
		}
		$player->sendForm($this->getForm($playerName));
	}

	public function call() : callable {
		return function (Player $player, $data) {
			if ($data === null) {
				return;
			}
			if (!$this->menuActive) {
				Utils::processMenu($this->getBackRoute(), $player);
				return;
			}
			call_user_func($this->getCallable(), $this->getOptions()[$data[0]]);
		};
	}

	private function getForm(string $playerName): CustomForm {
		$menu = new CustomForm($this->call());
		$menu->setTitle(Utils::getText($this->getUserEntity()->getName(), "SELECT_PLAYER_PANEL_TITLE"));
		$this->options = [];
		foreach (MainAPI::$users as $name => $user) {
			if (strpos($name, $playerName) !== false && $name !== $this->getUserEntity()->getName() && count($this->getOptions()) < FactionMasterInvitationImprove::getConfigF("limit-selected-player")) {
				$this->options[] = $user->getName();
			}
		}
		if (count($this->getOptions()) != 0) {
			$menu->addDropdown(Utils::getText($this->getUserEntity()->getName(), "SELECT_PLAYER_PANEL_CONTENT"), $this->getOptions());
			$this->menuActive = true;
		} else {
			$menu->addLabel(Utils::getText($this->getUserEntity()->getName(), "SELECT_PLAYER_PANEL_ERROR", [
				"needle" => $playerName
			]));
		}
		return $menu;
	}
}