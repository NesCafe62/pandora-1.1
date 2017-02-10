<?php
defined ("CORE_EXEC") or die('Access Denied');

echo '<html>';

	echo '<head>';

		echo '<include:head />';
		
	echo '</head>';

	echo '<body>';

		echo '<include:body_start />';


		echo $app::template('page');

		echo '<include:body_end />';
		
		echo '<include:scripts />';
		
	echo '</body>';

echo '</html>';
