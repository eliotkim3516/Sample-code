<?php
error_reporting( E_ERROR ); 


print 'SCRIPT DISABLED IN CODE! PLEASE USE WITH EXTREME CAUTION!!!!!!!';
exit;

$query = "select Y from X where Y > 1000 order by Y asc";

$result = Database::select($query);	

foreach($result as $k => $row) {
	$next = $result[$k + 1]['Y'];
	if ($k == 0) {
		$current = $row['Y'];
	}


	if(!empty($next) && abs($next-$current) > 1) {
		$new = $current + 1;

		$q = "select * from Q where W = {$new}";
		$r = Database::select($q);	
		if (count($r) > 0) {
			$exists = true;
		} else {
			$exists = false;
		}

		while ($exists) {
			// print ' had to add one!'.PHP_EOL;
			$new++;

			$q = "select * from Q where W = {$new}";
			$r = Database::select($q);	
			if (count($r) > 0) {
				$exists = true;
			} else {
				$exists = false;
			}
		}


		$update_q = "update Q set W = {$new} where W = {$next}";

		$update_t = "update T set P = {$new} where P = {$next}";

		$update_v = "update V set Z = {$new} where Z = {$next}";

		$update_m = "update M set B = {$new} where B = {$next}";

		$insert_f = "insert into F (old_value, new_value) values ({$next}, {$new})";

		$myLinx->linx_query($update_q);
		$myLinx->linx_query($update_t);
		$myLinx->linx_query($update_v);
		$myLinx->linx_query($update_m);

		$myLinx->linx_query($insert_f);

		echo 'Updated '.$next.' to be '.$new.PHP_EOL;

		$current = $new;
	}

}

echo "done";