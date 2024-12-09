<?php
//ini_set('display_errors', 1);
//	ini_set('display_startup_errors', 1);
//	error_reporting(E_ALL);
$cj_username= getenv('CJ_USERNAME') ?: "vidlii";
$cj_password= getenv("CJ_PASSWORD") ?: "vidlii";
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="VidLii"');
    header('HTTP/1.0 401 Unauthorized');
    echo "stfu";
    exit;
} else if("{$cj_username}{$cj_password}" != "{$_SERVER['PHP_AUTH_USER']}{$_SERVER['PHP_AUTH_PW']}") {
    echo "stfu";
    exit;
}
    require_once "../_includes/init.php";
	set_time_limit(0);
	$root = "..";

	//GET CONVERTER VERSION
    // $Version = $DB->execute("SELECT value FROM options WHERE option_name='converting'", true)["value"];
	// if ($Version == 0) die("CONVERTER DISABLED!");
	
	//MAKE CONVERTER RUN FOREVER
//	while(true) {
		//CHECK IF THERE ARE MORE THAN THREE INSTANCES RUNNING
		// if (shell_exec("ps aux | grep -v \"grep\" | grep \"ffmpeg\" | wc -l") >= 3) {
		// 	die("TOO MANY INSTANCES OF FFMPEG RUNNING!");
		// }
		
		// if (shell_exec("ps aux | grep -v \"grep\" | grep \"converter.php\" | wc -l") >= 4) {
		// 	die("TOO MANY INSTANCES OF CONVERTER.PHP RUNNING!");
		// }
		
		//CHECK IF THERE ARE TOO MANY VIDEOS CONVERTING AT THE SAME TIME
		while(true) {
			$Query = $DB->execute("SELECT COUNT(*) as amount FROM converting WHERE convert_status='1' LIMIT 3", true)["amount"];
			if ($Query >= 3) sleep(10);
			else break;
		}
		
		//GET LATEST UPLOADED VIDEO TO CONVERT
		$Query = $DB->execute("SELECT url FROM converting WHERE convert_status = '0' ORDER BY uploaded_on ASC LIMIT 1", true);
		if ($DB->RowNum == 0) {
			//IF THERE ARE NO VIDEOS TO CONVERT, SLEEP 10 SECONDS UNTIL THE NEXT QUERY
			die("Nothing to process");
			// sleep(10);
//			continue;
		}
		//GET VIDEO TO CONVERT'S URL
		$URL = $Query["url"];
		$Info = $DB->execute("SELECT users.partner, videos.status, videos.file FROM videos, users WHERE videos.url = '$URL' AND users.username = videos.uploaded_by LIMIT 1", true);
		
		if ($DB->RowNum == 0) {
			//IF VIDEO DOES NOT EXIST, DELETE ROW AND JUMP TO THE NEXT ONE
			$DB->modify("DELETE FROM converting WHERE url = '$URL'");
			$DB->modify("UPDATE converting SET queue = GREATEST(0, queue - 1)");
			die("Video does not exist");
//			continue;
		} else {
			//VIDEO EXISTS, FETCH INFO FROM THE VIDEO AND THE USER
			$Partner    = $Info["partner"];
			$Status     = $Info["status"];
			$File       = $Info["file"];
			$DB->modify("UPDATE converting SET convert_status = 1 WHERE url = '$URL'");
			
			//IF NOT CHANGING VIDEO FILE, UPDATE VIDEO STATUS TO CONVERTING
			if ($Status != 2) $DB->modify("UPDATE videos SET status = 1 WHERE url = '$URL'");
		}
		
		
		// ABORT IF FILE DOESN'T EXIST
		$Video_File = glob("$root/usfi/conv/$URL.*");
		if (count($Video_File) == 0) {
			$DB->modify("DELETE FROM converting WHERE url = '$URL'");
			$DB->modify("UPDATE converting SET queue = GREATEST(0, queue - 1)");
			$DB->modify("UPDATE videos SET status = '-2' WHERE url = '$URL'");
			die("File usfi/conv_2/" . $URL . " does not exist");
//			continue;
		} else {
			$Video_File = $Video_File[0];
		}
		
		//SET UP FFMPEG
		$Video = new ffmpeg();
		$Video->Location = $Video_File;
		$Video->Get_Info();
		$Length = (float)$Video->Info->format->duration;
		
		//IF LENGTH EXCEEDS LIMIT, DELETE AND SKIP TO THE NEXT FILE
		if ($Partner == 0) { // NORMAL USER, 25 MINUTES
			if ($Length > 1500) {
				echo "$URL is over 25 minutes long";
				
				$Video = new Video($URL, $DB);
				$Video->delete();
				die("Video too long");
//				continue;
			}
		} else { // PARTNER, 30 MINUTES
			if ($Length > 2100) {
				echo "$URL is over 35 minutes long";
				
				$Video = new Video($URL, $DB);
				$Video->delete();
//				continue;
				die("Video too long");
			}
		}

		//SET UP FFMPEG CONVERSION OPTIONS
		if ($Partner == 0) {
			$Video->Resize(480);
			$Video->SampleRate = 44100;
			$Video->Framerate = "30";
			$Video->AudioBitrate = "128k";
			$Video->CRF = 25;
		} else {
			$Video->Resize(720);
			$Video->SampleRate = 44100;
			$Video->Framerate = "30";
			$Video->AudioBitrate = "128k";
			$Video->CRF = $Video->HD ? 25 : 23;
		}
		
		//CONVERT
		$Video->Output = "$root/usfi/v/$URL.mp4";
		
		//GIVE THE CONVERTER THREE CHANCES TO PROCESS THE VIDEO
		for($tries=0; $tries < 3; $tries++) {
			@unlink($Video->Output);
			$success = $Video->Convert();
			if ($success) break;
		}
		
		//ABORT IF CONVERSION FAILED
		if (!$success) {
			@unlink($Video_File);
			@unlink($Video->Output);
			$DB->modify("DELETE FROM converting WHERE url = '$URL'");
			$DB->modify("UPDATE converting SET queue = GREATEST(0, queue - 1)");
			$DB->modify("UPDATE videos SET status = '-2' WHERE url = '$URL'");
			die("Conversion failed");
//			continue;
		}
		
		//CREATE FILE COLUMN VALUE
		$Filename = "";
		while($File == $Filename) {
			$Filename = random_string("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_", 80);
		}
		
		//IF VIDEO IS HD, CREATE LOW QUALITY VERSION
		if ($Video->HD) {
			rename($Video->Output, "$root/usfi/v/$URL.$Filename.720.mp4");
			$HD = 1;
			
			$Video->Resize(240);
			$Video->SampleRate = 44100;
			$Video->Framerate = "30";
			$Video->AudioBitrate = "128k";
			$Video->CRF = 25;
			
			//RUN UNTIL IT CONVERTS THE VIDEO SUCCESSFULLY
			for($tries=0; $tries < 3; $tries++) {
				@unlink($Video->Output);
				$success = $Video->Convert();
				if ($success) break;
			}
			
			//ABORT IF CONVERSION FAILED
			if (!$success) {
				@unlink($Video_File);
				@unlink($Video->Output);
				$DB->modify("DELETE FROM converting WHERE url = '$URL'");
				$DB->modify("UPDATE converting SET queue = GREATEST(0, queue - 1)");
				$DB->modify("UPDATE videos SET status = '-2' WHERE url = '$URL'");
				die("Conversion failed 2");
//				continue;
			}
		} else {
			$HD = 0;
		}

		//CREATE THUMBNAILS IF CUSTOM HASN'T BEEN UPLOADED
		if ($Status != 2 && !file_exists("$root/usfi/thmp/$URL.jpg")) {
			if ($Length <= 6) {
				$Thumbnail_Sec = 0;
			} else {
				$Thumbnail_Sec = mt_rand(0, $Length);
			}

			$Video->Make_Thumbnails($Thumbnail_Sec, $URL);
		}
		
		//DELETE ORIGINAL FILES
		unlink($Video_File);
		if ($Status == 2) {
			foreach(glob("$root/usfi/v/$URL.$File.*") as $v) {
				unlink($v);
			}
		}
		
		//RENAME CONVERTED FILE AND UPDATE TABLES
		if (rename($Video->Output, "$root/usfi/v/$URL.$Filename.mp4")) {
			$DB->modify("DELETE FROM converting WHERE url = '$URL'");
			$DB->modify("UPDATE converting SET queue = GREATEST(0, queue - 1)");
			$DB->modify("UPDATE videos SET status = '2', hd = '$HD', length = '$Length', thumbs = 1, file = '$Filename' WHERE url = '$URL'");
		}
//		file_put_contents("$root/logs/".mt_rand(1000,100000).".txt", "dick");
//	}
	
	die("QUITTING!");
	// exit();
