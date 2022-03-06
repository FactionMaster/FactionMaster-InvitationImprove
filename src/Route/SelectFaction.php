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
use pocketmine\player\Player;
use ShockedPlot7560\FactionMaster\API\MainAPI;
use ShockedPlot7560\FactionMaster\Database\Entity\UserEntity;
use ShockedPlot7560\FactionMaster\libs\Vecnavium\FormsUI\CustomForm;
use ShockedPlot7560\FactionMaster\Route\Route;
use ShockedPlot7560\FactionMaster\Route\RouteBase;
use ShockedPlot7560\FactionMaster\Utils\Utils;
use ShockedPlot7560\FactionMasterInvitationImprove\FactionMasterInvitationImprove;
use function call_user_func;
use function count;
use function strpos;

class SelectFaction extends RouteBase implements Route {
	const SLUG = "selectFaction";

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
			$factionName = $params[0];
		} else {
			throw new InvalidArgumentException("\$params has not the good format");
		}
		$player->sendForm($this->getForm($factionName));
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

	private function getForm(string $factionName): CustomForm {
		$menu = new CustomForm($this->call());
		$menu->setTitle(Utils::getText($this->getUserEntity()->getName(), "SELECT_FACTION_PANEL_TITLE"));
		$this->options = [];
		foreach (MainAPI::$factions as $name => $faction) {
			if (strpos($name, $factionName) !== false && $name !== $this->getUserEntity()->getFactionName() && count($this->getOptions()) < FactionMasterInvitationImprove::getConfigF("limit-selected-faction")) {
				$this->options[] = $faction->getName();
			}
		}
		if (count($this->getOptions()) != 0) {
			$menu->addDropdown(Utils::getText($this->getUserEntity()->getName(), "SELECT_FACTION_PANEL_CONTENT"), $this->getOptions());
			$this->menuActive = true;
		} else {
			$menu->addLabel(Utils::getText($this->getUserEntity()->getName(), "SELECT_FACTION_PANEL_ERROR", [
				"needle" => $factionName
			]));
		}
		return $menu;
	}
}