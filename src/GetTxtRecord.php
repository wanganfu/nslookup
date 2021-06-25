<?php
declare(strict_types = 1);

namespace annon;


final class GetTxtRecord
{
    /**
     * @throws \Exception
     */
    final public static function getTXTRecord($domain): array
    {
        $request = self::makeHeader($domain);
        $response = self::udpGet($request, '8.8.8.8');

        $answers  = substr($response, strlen($request));
        $answer_count = unpack('n', substr($response, 6,2))[1];
		$auth_count = unpack('n', substr($response, 8, 2))[1];
		
		if ($auth_count > 0) {
			throw new \Exception("auth count is 0\n");
		}
		
        $answers  = str_split($answers, strlen($answers) / $answer_count);

        $records = [];
        foreach ($answers as $answer) {
            $records[] = self::unPack($answer, $domain);
        }
        return $records;
    }

    /**
     * @param $bytes
     * @param $domain
     * @return string
     */
    final public static function unPack($bytes, $domain): array
    {
        $name  = substr($bytes, 0, 2); //49164
        $type  = substr($bytes, 2, 2); //16 menus txt
        $class = substr($bytes, 4, 2); //1 menus IN
        $ttl1  = substr($bytes, 6, 2); //ttl1
        $ttl   = substr($bytes, 8, 2); //ttl2
        $dlen  = substr($bytes, 10, 2); //43
        $tlen  = substr($bytes, 12, 1); //44

        $record['name'] = $domain;
        $record['type'] = unpack('n', $type)[1];
        $record['class'] = unpack('n', $class)[1];
        $record['ttl'] = unpack('n', $ttl1)[1] * 65536 + unpack('n', $ttl)[1];
        $record['data_len'] = unpack('n', $dlen)[1];
        $record['txt_len'] = unpack('C', $tlen)[1];
        $record['txt'] = substr($bytes, 13, $record['txt_len']);

        return $record;
    }

    /**
     * @param $domain
     * @return string
     */
    final public static function makeHeader($domain): string
    {
        $req_id = rand(1, 65530);
        $data = pack('n6', $req_id, 0x0100, 1, 0, 0, 0); // header
        foreach (explode('.', $domain) as $bit) { // domain
            $l = strlen($bit);
            $data .= chr($l) . $bit;
        }
        $data .= pack('C', 0); // separator
        return $data . pack('n2', 16, 1); // query_type, query_class
    }

    /**
     * @throws \Exception
     */
    final public static function udpGet($sendMsg = '', $ip = '114.114.114.114', $port = '53'): string
    {
        $handle = stream_socket_client("udp://{$ip}:{$port}", $errno, $err);
        if (!$handle) {
            throw new \Exception("ERROR: {$errno} - {$err}\n");
        }
        fwrite($handle, $sendMsg, strlen($sendMsg));
        $result = fread($handle, 1024);
        fclose($handle);
        if (!$result) {
            throw new \Exception("read socket buffer returns false!\n");
        }
        return $result;
    }
}