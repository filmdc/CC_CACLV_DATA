<?php

namespace App\Exceptions;

use App\Models\Contract;
use Exception;

class ContractFullyUtilizedException extends Exception
{
    protected Contract $contract;

    protected float $remainingFunds;

    protected ?float $requestedAmount;

    public function __construct(
        string $message,
        Contract $contract,
        float $remainingFunds = 0,
        ?float $requestedAmount = null
    ) {
        parent::__construct($message);
        $this->contract = $contract;
        $this->remainingFunds = $remainingFunds;
        $this->requestedAmount = $requestedAmount;
    }

    /**
     * Get the contract that is fully utilized
     */
    public function getContract(): Contract
    {
        return $this->contract;
    }

    /**
     * Get the remaining funds on the contract
     */
    public function getRemainingFunds(): float
    {
        return $this->remainingFunds;
    }

    /**
     * Get the requested amount that was denied
     */
    public function getRequestedAmount(): ?float
    {
        return $this->requestedAmount;
    }

    /**
     * Get a user-friendly error message
     */
    public function getUserMessage(): string
    {
        if ($this->remainingFunds <= 0) {
            return sprintf(
                'The contract "%s" is fully utilized. No additional expenses can be recorded.',
                $this->contract->title
            );
        }

        if ($this->requestedAmount !== null) {
            return sprintf(
                'The requested amount of $%s exceeds the remaining budget of $%s for contract "%s".',
                number_format($this->requestedAmount, 2),
                number_format($this->remainingFunds, 2),
                $this->contract->title
            );
        }

        return sprintf(
            'Contract "%s" has insufficient remaining budget ($%s).',
            $this->contract->title,
            number_format($this->remainingFunds, 2)
        );
    }

    /**
     * Get contract details for display
     */
    public function getContractDetails(): array
    {
        return [
            'id' => $this->contract->id,
            'title' => $this->contract->title,
            'contract_number' => $this->contract->contract_number,
            'grant_code' => $this->contract->grant_code,
            'amount' => $this->contract->amount,
            'total_spent' => $this->contract->total_spent,
            'remaining' => $this->remainingFunds,
            'utilization_percentage' => $this->contract->utilization_percentage,
        ];
    }
}
