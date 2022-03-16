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

namespace ShockedPlot7560\FactionMasterInvitationImprove;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use ShockedPlot7560\FactionMaster\Extension\Extension;
use ShockedPlot7560\FactionMaster\libs\JackMD\ConfigUpdater\ConfigUpdater;
use ShockedPlot7560\FactionMaster\libs\JackMD\UpdateNotifier\UpdateNotifier;
use ShockedPlot7560\FactionMaster\Manager\ExtensionManager;
use ShockedPlot7560\FactionMaster\Route\RouterFactory;
use ShockedPlot7560\FactionMasterInvitationImprove\Route\NewAllianceInvitation;
use ShockedPlot7560\FactionMasterInvitationImprove\Route\NewInvitation;
use ShockedPlot7560\FactionMasterInvitationImprove\Route\NewMemberInvitation;
use ShockedPlot7560\FactionMasterInvitationImprove\Route\SelectFaction;
use ShockedPlot7560\FactionMasterInvitationImprove\Route\SelectPlayer;
use function mkdir;

class FactionMasterInvitationImprove extends PluginBase implements Extension {
	private $LangConfig = [];
	private static $instance;

	public function onLoad(): void {
		self::$instance = $this;
		ExtensionManager::registerExtension($this);

		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();
		$this->saveResource('fr_FR.yml');
		$this->saveResource('en_EN.yml');
		$this->saveResource('tr_TR.yml');
		$this->saveResource('pt_BR.yml');
                $this->saveResource('es_SPA.yml');
		$this->saveResource('ru_RU.yml');
		$this->saveResource('config.yml');
		ConfigUpdater::checkUpdate($this, $this->getConfig(), "file-version", 1);
		ConfigUpdater::checkUpdate($this, new Config($this->getDataFolder() . "fr_FR.yml", Config::YAML), "file-version", 1);
		ConfigUpdater::checkUpdate($this, new Config($this->getDataFolder() . "en_EN.yml", Config::YAML), "file-version", 1);
		ConfigUpdater::checkUpdate($this, new Config($this->getDataFolder() . "tr_TR.yml", Config::YAML), "file-version", 1);
		ConfigUpdater::checkUpdate($this, new Config($this->getDataFolder() . "pt_BR.yml", Config::YAML), "file-version", 1);
                ConfigUpdater::checkUpdate($this, new Config($this->getDataFolder() . "es_SPA.yml", Config::YAML), "file-version", 1);
		ConfigUpdater::checkUpdate($this, new Config($this->getDataFolder() . "ru_RU.yml", Config::YAML), "file-version", 1);
		$this->LangConfig = [
			"FR" => new Config($this->getDataFolder() . "fr_FR.yml", Config::YAML),
			"TR" => new Config($this->getDataFolder() . "tr_TR.yml", Config::YAML),
			"EN" => new Config($this->getDataFolder() . "en_EN.yml", Config::YAML),
			"BR" => new Config($this->getDataFolder() . "pt_BR.yml", Config::YAML),
			"RU" => new Config($this->getDataFolder() . "ru_RU.yml", Config::YAML),
                        "SPA" => new Config($this->getDataFolder() . "es_SPA.yml", Config::YAML),
		];
	}

	public function onEnable(): void {
		UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
	}

	public function execute(): void {
		RouterFactory::registerRoute(new SelectPlayer());
		if ($this->getConfigF("member-impact") == true) {
			RouterFactory::registerRoute(new NewMemberInvitation(), true);
		}
		RouterFactory::registerRoute(new SelectFaction());
		if ($this->getConfigF("join-impact") == true) {
			RouterFactory::registerRoute(new NewInvitation(), true);
		}
		if ($this->getConfigF("alliance-impact") == true) {
			RouterFactory::registerRoute(new NewAllianceInvitation(), true);
		}
	}

	public function getLangConfig(): array {
		return $this->LangConfig;
	}

	public static function getInstance() : self {
		return self::$instance;
	}

	public function getExtensionName() : string {
		return 'FactionMaster-InvitationImprove';
	}

	public static function getConfigF(string $key) {
		$Config = new Config(self::getInstance()->getDataFolder() . "config.yml", Config::YAML);
		return $Config->get($key);
	}
}
