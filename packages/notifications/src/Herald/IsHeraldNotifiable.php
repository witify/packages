<?php

namespace Witify\Notifications\Herald;

interface IsHeraldNotifiable
{
    public function heraldNotifiable(): HeraldNotifiable;
}
