<?php

use \Symfony\Component\HttpClient\HttpClient;

class Webservice
{
    protected $url;
    protected $password;
    protected $resource;
    protected $id;
    protected $client;

    public function __construct($url, $password)
    {
        $this->url = $url;
        $this->password = $password;
    }

    public function get($options)
    {
        if (isset($options['resource']))
            $this->resource = $options['resource'];

        if (isset($options['id']))
            $this->id = $options['id'];

        if (!$this->resource)
            throw new Exception('Resource is required');

        $this->url .= '/' . $this->resource . '/';

        if ($this->id) {
            $this->url .= '/' . $this->id;
        }

        $extraOptions = [];

        foreach ($options as $key => $value) {
            switch ($key) {
                case 'filter':
                case 'display':
                case 'sort':
                case 'limit':
                    $extraOptions[$key] = $value;
                    break;
            }
        }

        $extraOptions = http_build_query($extraOptions);

        if (!empty($extraOptions))
            $this->url .= '?' . $extraOptions;

        $xml = $this->execute([
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);

        if (!$this->id) {
            if (empty($xml))
                return false;
            $result = $this->xmlToArray($xml);

            if (!isset($result[$this->resource]['id']))
                $result = $this->xmlToArray($xml)[$this->resource];

            $tmp = [];

            foreach ($result as $items) {
                $tmp[$this->resource][] = $items;
            }
            return $tmp;
        }

        return $this->xmlToArray($xml);
    }

    public function add($options)
    {
        $resource = $options['resource'] ?? null;
        $data = $options['data'] ?? null;

        if (!$resource)
            throw new Exception('Resource is required');

        $this->url .= '/' . $resource . '/';

        if (!($data && is_array($data)))
            throw new Exception('Error');

        return $this->execute([
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => http_build_query($data)
        ]);
    }

    public function edit($options)
    {
        $resource = $options['resource'] ?? null;
        $id = $options['id'] ?? null;
        $data = $options['data'] ?? null;

        if (!$resource)
            throw new Exception('Resource is required');

        $this->url .= '/' . $resource . '/';

        if (!$id)
            throw new Exception('Id entity is required');

        $this->url .= $id;

        if (!$data)
            throw new Exception('Data is required');

        return $this->execute([
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => http_build_query($data),
        ]);
    }

    public function delete($options)
    {
        $resource = $options['resource'] ?? null;
        $id = $options['id'] ?? null;

        if (!$resource)
            throw new Exception('Resource is required');

        if (!$id)
            throw new Exception('Id entity is required');

        $this->url .= '/' . $resource . '/' . $id;

        return $this->execute([
            CURLOPT_CUSTOMREQUEST => 'DELETE'
        ]);
    }

    private function execute($curlOptions)
    {
        $curl = curl_init();

        $defaultCurlOptions = array(
            CURLOPT_URL => $this->urlFormatter(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERPWD => ":$this->password",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded",
            ),
        );

        $options = $defaultCurlOptions + $curlOptions;

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($err) {
            throw new Exception('cURL Error #:' . $err);
        } else {
            return [
                'message' => $response,
                'code' => $httpCode
            ];
        }
    }

    private function xmlToArray($xml)
    {
        $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);


        return json_decode(json_encode($xml), TRUE);
    }

    private function urlFormatter()
    {
        return preg_replace('#/+#', '/', $this->url);
    }
}