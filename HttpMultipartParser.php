<?php
class HttpMultipartParser
{
	public static function populate_post_stdin()
	{
		$parsed = self::parse_stdin();
		$_POST = $parsed['variables'];
		$_FILES = $parsed['files'];
	}

	public static function populate_put_stdin()
	{
		$parsed = self::parse_stdin();
		$_PUT = $parsed['variables'];
		$_FILES = $parsed['files'];
	}

	public static function parse_stdin()
	{
		$stream = fopen("php://input", 'r');

		return self::parse_multipart($stream);
	}

	public static function parse_multipart($stream, $boundary = null)
	{
		$return = array('variables' => array(), 'files' => array());

		$partInfo = null;

		while(($lineN = fgets($stream)) !== false)
		{
			if(strpos($lineN, '--') === 0)
			{
				if(!isSet($boundary) || $boundary == null)
				{
					$boundary = rtrim($lineN);
				}
				continue;
			}

			$line = rtrim($lineN);

			if($line == '')
			{
				if(!empty($partInfo['Content-Disposition']['filename']))
				{
					self::parse_file($stream, $boundary, $partInfo, $return['files']);
				}
				elseif($partInfo != null)
				{
					self::parse_variable($stream, $boundary, $partInfo['Content-Disposition']['name'], $return['variables']);
				}
				$partInfo = null;
				continue;
			}

			$delim = strpos($line, ':');

			$headerKey = substr($line, 0, $delim);
			$headerVal = ltrim($line, $delim + 1);

			$partInfo[$headerKey] = self::parse_header_value($headerVal, $headerKey);
		}

		fclose($stream);
		return $return;
	}

	public static function parse_header_value($line, $header = '')
	{
		$retval = array();
		$regex  = '/(^|;)\s*(?P<name>[^=:,;\s"]*):?(=("(?P<quotedValue>[^"]*(\\.[^"]*)*)")|(\s*(?P<value>[^=,;\s"]*)))?/mx';

		$matches = null;
		preg_match_all($regex, $line, $matches, PREG_SET_ORDER);

		for($i = 0; $i < count($matches); $i++)
		{
			$match = $matches[$i];
			$name = $match['name'];
			$quotedValue = $match['quotedValue'];
			if(empty($quotedValue))
			{
				$value = $match['value'];
			}
			else {
				$value = stripcslashes($quotedValue);
			}
			if($name == $header && $i == 0)
			{
				$name = 'value';
			}
			$retval[$name] = $value;
		}
		return $retval;
	}

	public static function parse_variable($stream, $boundary, $name, &$array)
	{
		$fullValue = '';
		$lastLine = null;
		while(($lineN = fgets($stream)) !== false && strpos($lineN, $boundary) !== 0)
		{
			if($lastLine != null)
			{
				$fullValue .= $lastLine;
			}
			$lastLine = $lineN;
		}

		if($lastLine != null)
		{
			$fullValue .= rtrim($lastLine, '\r\n');
		}
		$array[$name] = $fullValue;

	}

	public static function parse_file($stream, $boundary, $info, &$array)
	{
		$tempdir = sys_get_temp_dir();

		$name = $info['Content-Disposition']['name'];
		$fileStruct['name'] = $info['Content-Disposition']['filename'];
		$fileStruct['type'] = $info['Content-Type']['value'];

		$array[$name] = &$fileStruct;

		if(empty($tempdir))
		{
			$fileStruct['error'] = UPLOAD_ERR_NO_TMP_DIR;
			return;
		}

		$tempname = tempnam($tempdir, 'php_upl');
		$outFP = fopen($tempname, 'wb');
		if($outFP === false)
		{
			$fileStruct['error'] = UPLOAD_ERR_CANT_WRITE;
			return;
		}

		$lastLine = null;
		while(($lineN = fgets($stream, 4096)) !== false)
		{
			if($lastLine != null)
			{
				if(strpos($lineN, $boundary) === 0) break;
				if(fwrite($outFP, $lastLine) === false)
				{
					$fileStruct = UPLOAD_ERR_CANT_WRITE;
					return;
				}
			}
			$lastLine = $lineN;
		}

		if($lastLine != null)
		{
			if(fwrite($outFP, rtrim($lastLine, '\r\n')) === false)
			{
				$fileStruct['error'] = UPLOAD_ERR_CANT_WRITE;
				return;
			}
		}
		$fileStruct['error'] = UPLOAD_ERR_OK;
		$fileStruct['size'] = filesize($tempname);
		$fileStruct['tmp_name'] = $tempname;
	}
}
?>
