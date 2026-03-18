<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 42 42"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            d="M 4 4 H 14 V 18 L 28 4 H 38 L 20 20 L 38 38 H 28 L 14 22 V 38 H 4 Z"
        />
    </svg>
</template>