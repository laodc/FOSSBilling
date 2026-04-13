<?php

declare(strict_types=1);
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

/**
 *Client management.
 */

namespace Box\Mod\Client\Api;

class Client extends \Api_Abstract
{
    /**
     * Get payments information.
     *
     * @return array return pager information and data
     */
    public function balance_get_list(array $data): array
    {
        $service = $this->di['mod_service']('Client', 'Balance');
        $data['client_id'] = $this->identity->id;

        [$q, $params] = $service->getSearchQuery($data);
        $page = $data['page'] ?? null;
        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
        $pager = $this->di['pager']->getPaginatedResultSet($q, $params, $per_page, $page);

        foreach ($pager['list'] as $key => $item) {
            $balance = $this->di['db']->getExistingModelById('ClientBalance', $item['id'], 'Balance not found');
            $pager['list'][$key] = $service->toApiArray($balance);
        }

        return $pager;
    }

    /**
     * Get client balance.
     *
     * @return float Returns balance
     */
    public function balance_get_total(): float
    {
        $service = $this->di['mod_service']('Client', 'Balance');

        return $service->getClientBalance($this->identity);
    }

    public function is_taxable(): bool
    {
        return $this->getService()->isClientTaxable($this->identity);
    }

    public function resend_email_verification(): bool
    {
        if ($this->identity->email_approved) {
            // Email is already validated, so we don't need to do so again
            return true;
        }

        return $this->getService()->sendEmailConfirmationForClient($this->identity);
    }

    /**
     * Allow a user to verify the email address.
     *
     * @param array $data user define data
     *
     * @return bool true on successful verification
     */
    public function confirm_email_verification_code(array $data): bool
    {
        if ($this->identity->email_approved) {
            // Email is already validated, so we don't need to do so again
            return true;
        }

        $required = [
            'verification_code' => 'Verification code required',
        ];

        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        return $this->getService()->approveClientEmailByHash($data['verification_code']);
    }
}
