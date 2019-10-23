<?php

/**
 * 阿里Oss
 */

namespace dux\files;

class OssDriver implements FilesInterface {

    protected $config = [
        'secret_id' => '',
        'secret_key' => '',
        'bucket' => '',
        'domain' => '',
        'url' => ''
    ];

    public function __construct($config = []) {
        $config['url'] = trim(str_replace('\\', '/', $config['url']), '/');
        $config['domain'] = trim(str_replace('\\', '/', $config['domain']), '/');
        $this->config = array_merge($this->config, $config);
    }

    public function checkPath($dir) {
        if (empty($this->config['secret_id']) || empty($this->config['secret_key']) || empty($this->config['bucket']) || empty($this->config['domain']) || empty($this->config['url'])) {
            throw new \Exception("Oss configuration does not exist!");
        }
        return true;
    }

    public function save($data, $info) {
        $file = $info['dir'] . $info['name'];
        $response = (new \GuzzleHttp\Client())->request('PUT', $this->getUrl($file, 'PUT', $info['mime']), [
            'body' => $data,
            'headers' => [
                'content-type' => $info['mime']
            ]
        ]);
        $reason = $response->getStatusCode();
        if ($reason <> 200) {
            throw new \Exception("Oss Upload failed!");
        }
        return $this->config['domain'] . $file;
    }

    public function del($file) {
        $response = (new \GuzzleHttp\Client())->request('DELETE', $this->getUrl($file, 'DELETE'));
        $reason = $response->getStatusCode();
        if ($reason <> 204) {
            throw new \Exception("Oss Delete failed!");
        }
        return true;
    }

    private function getUrl($name, $type, $mime = '') {
        $time = time() + 1800;
        $policy = [];
        $policy[] = $type;
        $policy[] = '';
        if ($mime) {
            $policy[] = $mime;
        } else {
            $policy[] = '';
        }
        $policy[] = $time;
        $policy[] = '/' . $this->config['bucket'] . '/' . trim($name, '/');
        $policy = implode("\n", $policy);
        $signature = base64_encode(hash_hmac('sha1', $policy, $this->config['secret_key'], true));
        $data = [
            'OSSAccessKeyId' => $this->config['secret_id'],
            'Expires' => $time,
            'Signature' => $signature,
        ];
        return $this->config['url'] . $name . '?' . http_build_query($data);
    }


}