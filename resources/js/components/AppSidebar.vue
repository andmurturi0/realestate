<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Building2, Inbox, LayoutGrid, MailQuestion, MessageSquare, Quote, Settings, Tags, Users, UsersRound } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();

const can = computed(() => page.props.can);
const badges = computed(() => page.props.badges);
const totalNewLeads = computed(() => (badges.value ? badges.value.messages + badges.value.offers + badges.value.requests : 0));

const inboxItems = computed(() => [
    { title: 'Mesazhe', href: '/dashboard/inbox/messages', icon: MessageSquare, count: badges.value?.messages ?? 0 },
    { title: 'Oferta', href: '/dashboard/inbox/offers', icon: Tags, count: badges.value?.offers ?? 0 },
    { title: 'Kërkesa', href: '/dashboard/inbox/requests', icon: MailQuestion, count: badges.value?.requests ?? 0 },
]);

const isActive = (href: string) => page.url === href || page.url.startsWith(`${href}?`);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-2 py-0">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :is-active="isActive('/dashboard')">
                            <Link href="/dashboard">
                                <LayoutGrid />
                                <span>Përmbledhje</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :is-active="isActive('/dashboard/properties')">
                            <Link href="/dashboard/properties">
                                <Building2 />
                                <span>Pronat</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton>
                            <Inbox />
                            <span>Inbox</span>
                            <span
                                v-if="totalNewLeads > 0"
                                class="ml-auto rounded-full bg-primary px-1.5 py-0.5 text-xs font-medium leading-none text-primary-foreground"
                            >
                                {{ totalNewLeads }}
                            </span>
                        </SidebarMenuButton>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem v-for="item in inboxItems" :key="item.href">
                                <SidebarMenuSubButton as-child :is-active="isActive(item.href)">
                                    <Link :href="item.href">
                                        <component :is="item.icon" />
                                        <span>{{ item.title }}</span>
                                        <span
                                            v-if="item.count > 0"
                                            class="ml-auto rounded-full bg-primary px-1.5 py-0.5 text-xs font-medium leading-none text-primary-foreground"
                                        >
                                            {{ item.count }}
                                        </span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </SidebarMenuItem>

                    <SidebarMenuItem v-if="can.manageAgents">
                        <SidebarMenuButton as-child :is-active="isActive('/dashboard/agents')">
                            <Link href="/dashboard/agents">
                                <Users />
                                <span>Agjentët</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem v-if="can.manageSettings">
                        <SidebarMenuButton as-child :is-active="isActive('/dashboard/testimonials')">
                            <Link href="/dashboard/testimonials">
                                <Quote />
                                <span>Dëshmitë</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem v-if="can.manageSettings">
                        <SidebarMenuButton as-child :is-active="isActive('/dashboard/team-members')">
                            <Link href="/dashboard/team-members">
                                <UsersRound />
                                <span>Ekipi</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem v-if="can.manageSettings">
                        <SidebarMenuButton as-child :is-active="isActive('/dashboard/settings')">
                            <Link href="/dashboard/settings">
                                <Settings />
                                <span>Cilësimet</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
