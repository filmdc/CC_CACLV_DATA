<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Phase 3B (Step 1 stub, Step 7 implementation): notifies configured
 * recipients (over_budget_email_recipients) when a project budget's
 * budgeted_amount is modified — increase or decrease.
 *
 * Step 1 carries the queueable shell only. Step 7 swaps the constructor
 * to take a BudgetModification model (created in that step's migration),
 * adds the toMail() body with old/new amount + comment, and gates mail
 * on the recipient's over_budget_email opt-out.
 *
 * Database channel always fires for users on the recipient list (bell
 * surface). Mail channel is opt-out-able.
 */
class BudgetModifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database'];
    }
}
