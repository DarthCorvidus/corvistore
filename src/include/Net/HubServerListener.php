<?php
namespace Net;
/**
 * @deprecated since version 0.0.1
 */
interface HubServerListener {
	function onConnect(string $name, int $id, mixed $newClient): void;
	function hasClientListener(string $name, int $id): bool;
	function getClientListener(string $name, int $id): HubClientListener;
	function onDetach(string $name, int $id): void;
}
