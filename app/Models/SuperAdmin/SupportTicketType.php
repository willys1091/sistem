<?php

namespace App\Models\SuperAdmin;

use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * App\Models\SuperAdmin\SupportTicketType
 *
 * @property int $id
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|SupportTicketType newModelQuery()
 * @method static Builder|SupportTicketType newQuery()
 * @method static Builder|SupportTicketType query()
 * @method static Builder|SupportTicketType whereCreatedAt($value)
 * @method static Builder|SupportTicketType whereId($value)
 * @method static Builder|SupportTicketType whereType($value)
 * @method static Builder|SupportTicketType whereUpdatedAt($value)
 * @mixin Eloquent
 * @property-read Collection|SupportTicket[] $tickets
 * @property-read int|null $tickets_count
 */
class SupportTicketType extends BaseModel
{

    public function tickets()
    {
        return $this->hasMany(SupportTicket::class, 'support_ticket_type_id');
    }

}
