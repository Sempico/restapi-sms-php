<?php 

namespace Sempico\RestApi;

class Sms 
{
    /**
	 * Api client
	 * @var ApiClient
	 */
    private $client;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    } 

    /**
     * Send sms
     *
     * One message: ['number' => [...], 'senderID' => '...', 'text' => '...', ...].
     * Several messages: a list of such arrays. Up to 100 phone numbers in one request.
     *
     * @param array $config
     * @return mixed
     */
    public function send(array $config)
    {
        $route = 'v1/send';

        return $this->client->curlRequest($route, 'POST', $config);
    }

    /**
     * Send SMS to the numbers of Groups (campaign).
     *
     * @param array $config senderID, text, id_group (required); dateStart, timeStart, timeStop, phone, id_group_excluded
     * @return mixed
     */
    public function sendBulk(array $config)
    {
        $route = 'v1/send-bulk';

        return $this->client->curlRequest($route, 'POST', $config);
    }

    /**
     * Get info about sms
     * @param array $config
     * @return mixed
     */
    public function getInfo(array $config)
    {
        $route = 'v1/sms-full-data';

        return $this->client->curlRequest($route, 'POST', $config);
    }

    /**
     * Make refactoring of sms
     * @param array $config
     * @return mixed
     */
    public function refactoring(array $config)
    {
        $route = 'v1/replacement';

        return $this->client->curlRequest($route, 'POST', $config);
    }

    /**
     * Get a general statistic
     * @param array $config groups_choose, destination, date_range, type_sms, id_name_delivery
     * @return mixed
     */
    public function stat(array $config = [])
    {
        $route = 'v1/stat';

        return $this->client->curlRequest($route, 'POST', $config);
    }
}
