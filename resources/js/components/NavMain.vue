<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';
import CollapsibleTrigger from './ui/collapsible/CollapsibleTrigger.vue';
import { ChevronRight } from 'lucide-vue-next';
import CollapsibleContent from './ui/collapsible/CollapsibleContent.vue';
import Collapsible from './ui/collapsible/Collapsible.vue';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();

const hasActiveChild = (item: NavItem): boolean => {
    if (item.href && isCurrentUrl(item.href)) return true;
    if (item.children) {
        return item.children.some((child) => hasActiveChild(child));
    }
    return false;
};

</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Master Data</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">

                <Collapsible
                    v-if="item.children && item.children.length > 0"
                    as-child
                    :default-open="hasActiveChild(item)"
                    class="group/collapsible"
                >
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton :tooltip="item.title" class="cursor-pointer">
                                <component :is="item.icon" v-if="item.icon" />
                                <span>{{ item.title }}</span>
                                <ChevronRight
                                    class="ml-auto w-4 h-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <template v-for="subItem in item.children" :key="subItem.title">

                                    <Collapsible
                                        v-if="subItem.children && subItem.children.length > 0"
                                        as-child
                                        :default-open="hasActiveChild(subItem)"
                                        class="group/sub-collapsible"
                                    >
                                        <SidebarMenuSubItem>
                                            <CollapsibleTrigger as-child>
                                                <SidebarMenuSubButton
                                                    class="w-full flex justify-between cursor-pointer">
                                                    <span>{{ subItem.title }}</span>
                                                    <ChevronRight
                                                        class="w-4 h-4 transition-transform duration-200 group-data-[state=open]/sub-collapsible:rotate-90" />
                                                </SidebarMenuSubButton>
                                            </CollapsibleTrigger>

                                            <CollapsibleContent>
                                                <SidebarMenuSub
                                                    class="pr-0 mr-0 border-l border-gray-200 dark:border-gray-700 ml-2 pl-2 mt-1">
                                                    <SidebarMenuSubItem v-for="grandChild in subItem.children"
                                                        :key="grandChild.title">
                                                        <SidebarMenuSubButton as-child
                                                            :is-active="isCurrentUrl(grandChild.href || '')">
                                                            <Link v-if="grandChild.href" :href="grandChild.href">
                                                                <span>{{ grandChild.title }}</span>
                                                            </Link>
                                                        </SidebarMenuSubButton>
                                                    </SidebarMenuSubItem>
                                                </SidebarMenuSub>
                                            </CollapsibleContent>
                                        </SidebarMenuSubItem>
                                    </Collapsible>

                                    <SidebarMenuSubItem v-else>
                                        <SidebarMenuSubButton as-child :is-active="isCurrentUrl(subItem.href || '')">
                                            <Link v-if="subItem.href" :href="subItem.href">
                                                <span>{{ subItem.title }}</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>

                                </template>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>

                <SidebarMenuItem v-else>
                    <SidebarMenuButton as-child :is-active="isCurrentUrl(item.href || '')" :tooltip="item.title">
                        <Link v-if="item.href" :href="item.href">
                            <component :is="item.icon" v-if="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>