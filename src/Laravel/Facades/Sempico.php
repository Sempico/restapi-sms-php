<?php

declare(strict_types=1);

namespace Sempico\RestApi\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed me()
 * @method static array login(string $username, string $password)
 * @method static mixed logout()
 * @method static mixed register(array $data)
 * @method static mixed sendSms(string|array $numbers, string $senderId, string $text, array $options = [])
 * @method static mixed send(array $messages)
 * @method static mixed hlr(string|array $numbers, string $senderId = 'HLR', array $options = [])
 * @method static mixed sendBulk(array $data)
 * @method static mixed smsInfo(array $filters = [])
 * @method static mixed replacement(array $data)
 * @method static mixed stat(array $filters = [])
 * @method static mixed groups(array $filters = [])
 * @method static mixed createGroup(string $name, array $options = [])
 * @method static mixed deleteGroups(array $groupIds)
 * @method static mixed addNumbersToGroup(int $groupId, array $numbers)
 * @method static mixed searchNumbersInGroup(int $groupId, array $numbers)
 * @method static mixed deleteNumbersFromGroup(int $groupId, array $numbers)
 * @method static mixed blackListAdd(array $numbers)
 * @method static mixed blackListSearch(array $numbers)
 * @method static mixed blackListDelete(array $numbers)
 * @method static mixed productPrice(array $filters = [])
 * @method static mixed accountPrice(array $filters = [])
 * @method static mixed countryPrice(array $filters = [])
 * @method static mixed countryByContinent()
 * @method static \Sempico\RestApi\ApiClient getClient()
 *
 * @see \Sempico\RestApi\Sempico
 */
class Sempico extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'sempico';
    }
}
