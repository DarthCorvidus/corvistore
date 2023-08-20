<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Net;
interface HubServerListener {
	function onConnect(string $name, int $id, $newClient);
	function hasClientListener(string $name, int $id): bool;
	function getClientListener(string $name, int $id): HubClientListener;
	function onDetach(string $name, int $id);
}
