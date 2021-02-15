<?php

class Webservice
{
    protected $url;
    protected $password;
    protected $resource;
    protected $id;

    public function __construct($url, $password)
    {
        $this->url = $url;
        $this->password = $password;
    }

    public function get($options)
    {
        if (isset($options['resource'])) {
            $this->resource = $options['resource'];
        }
        if (isset($options['id'])) {
            $this->id = $options['id'];
        }

        if ($this->resource) {
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

            if ($this->id) {
                return $this->xmlToArray($xml);
            } else {

                if (empty($xml)) {
                    return false;
                }

                $result = $this->xmlToArray($xml)[$this->resource];
                $tmp = [];

                foreach ($result as $items) {
                    $tmp[$this->resource][] = $items;
                }
                return $tmp;
            }
        } else {
            throw new Exception('Resource is required');
        }
    }

    public function add($options)
    {
        $resource = $options['resource'];
        $data = $options['data'];

        if ($resource) {
            $this->url .= '/' . $resource . '/';

            if ($data && is_array($data)) {
                return $this->execute([
                    CURLOPT_POST => 1,
                    CURLOPT_POSTFIELDS => http_build_query($data)
                ]);
            } else {
                throw new Exception('Error');
            }
        } else {
            throw new Exception('Resource is required');
        }
    }

    public function edit($options)
    {
        $resource = $options['resource'];
        $id = $options['id'];
        $data = $options['data'];

        if ($resource) {
            $this->url .= '/' . $resource . '/';

            if ($id) {
                $this->url .= $id;

                if ($data) {
                    $this->execute([
                        CURLOPT_CUSTOMREQUEST => 'PUT',
                        CURLOPT_POSTFIELDS => http_build_query($data),
                    ]);
                } else {
                    throw new Exception('Data is required');
                }
            } else {
                throw new Exception('Id is required');
            }
        } else {
            throw new Exception('Resource is required');
        }
    }

    public function delete($options)
    {
        $resource = $options['resource'];
        $id = $options['id'];

        if ($resource) {
            if ($id) {
                $this->url .= '/' . $resource . '/' . $id;

                return $this->execute([
                    CURLOPT_CUSTOMREQUEST => 'DELETE'
                ]);
            } else {
                throw new Exception('Id is required');
            }
        } else {
            throw new Exception('Resource is required');
        }
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

        curl_close($curl);

        if ($err) {
            throw new Exception('cURL Error #:' . $err);
        } else {
            return $response;
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