<?php
class GMError extends GMController
{
	public function doDefault()
	{
		dbdError::doError($this);
	}
}
?>