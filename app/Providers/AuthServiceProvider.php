<?php

namespace App\Providers;

use App\Models\ContactMessage;
use App\Models\Property;
use App\Models\PropertyOffer;
use App\Models\PropertyRequest;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\User;
use App\Policies\LeadPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\TeamMemberPolicy;
use App\Policies\TestimonialPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Property::class => PropertyPolicy::class,
        ContactMessage::class => LeadPolicy::class,
        PropertyOffer::class => LeadPolicy::class,
        PropertyRequest::class => LeadPolicy::class,
        User::class => UserPolicy::class,
        Testimonial::class => TestimonialPolicy::class,
        TeamMember::class => TeamMemberPolicy::class,
    ];
}
