<?php

/***
 *        __  ___                           __
 *       / / / (_)__  _________ ___________/ /_  __  __
 *      / /_/ / / _ \/ ___/ __ `/ ___/ ___/ __ \/ / / /
 *     / __  / /  __/ /  / /_/ / /  / /__/ / / / /_/ /
 *    /_/ /_/_/\___/_/   \__,_/_/   \___/_/ /_/\__, /
 *                                            /____/
 *
 * HRKChat - Chat & nametag formatter that respects Role Hierarchy
 * Copyright (C) 2019-Present CortexPE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace CortexPE\HRKChat;


use CortexPE\Hierarchy\event\MemberRoleUpdateEvent;
use CortexPE\Hierarchy\Hierarchy;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;

class EventListener implements Listener {
	/** @var HRKChat */
	protected $plugin;
	/** @var string[] */
	protected $chatFormats = [];
	/** @var string[] */
	protected $nameTagFormats = [];

	public function __construct(HRKChat $plugin, array $config) {
		$this->plugin = $plugin;
		$this->chatFormats = $config["chatFormat"];
		$this->nameTagFormats = $config["nameTagFormat"];
	}

	/**
	 * @param MemberRoleUpdateEvent $ev
	 *
	 * @priority        LOW
	 * @ignoreCancelled true
	 */
	public function onRoleChange(MemberRoleUpdateEvent $ev): void {
		$m = $ev->getMember();
		$p = $m->getPlayer();
		if($p instanceof Player) {
			$roles = $m->getRoles();
			$topRolePosition = PHP_INT_MIN;
			$roleID = Hierarchy::getRoleManager()->getDefaultRole()->getId();
			foreach($roles as $role) {
				if(
					isset($this->nameTagFormats[$role->getId()]) &&
					$role->getPosition() > $topRolePosition
				) {
					$topRolePosition = $role->getPosition();
					$roleID = $role->getId();
				}
			}

			$p->setNameTag($this->plugin->getPlaceholderManager()->processString($this->nameTagFormats[$roleID], $m));
		}
	}

	/**
	 * @param PlayerChatEvent $ev
	 *
	 * @priority        LOW
	 * @ignoreCancelled true
	 */
	public function onChat(PlayerChatEvent $ev) {
		$member = Hierarchy::getMemberFactory()->getMember(($p = $ev->getPlayer()));

		$roles = $member->getRoles();
		$topRolePosition = PHP_INT_MIN;
		$roleID = Hierarchy::getRoleManager()->getDefaultRole()->getId();
		foreach($roles as $role) {
			if(
				isset($this->chatFormats[$role->getId()]) &&
				$role->getPosition() > $topRolePosition
			) {
				$topRolePosition = $role->getPosition();
				$roleID = $role->getId();
			}
		}
		$phMgr = $this->plugin->getPlaceholderManager();
		$msg = str_replace(
			$phMgr->getPrefix() . "msg" . $phMgr->getSuffix(),
			$ev->getMessage(),
			$this->chatFormats[$roleID]
		);

		$ev->setFormat($phMgr->processString($msg, $member));
	}
}