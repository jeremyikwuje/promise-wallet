<?php

class RespondController extends Controller
{
    public function withdrawals()
    {
        $withdrawals = Ledger::withdrawals("InProgress");

        if (isset($withdrawals["error"]))
            $this->errorResponse($withdrawals["error"], 502);

        foreach($withdrawals as $w)
        {
            //$w = (object) $w;

            if (!isset($w->txId))
                continue;

            $withdrawal = Ledger::completeWithdrawal($w->id, $w->txId);
        }

        $this->successResponse("Results", $withdrawals);
    }
}