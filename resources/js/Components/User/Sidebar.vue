<script setup>
import {Link} from "@inertiajs/vue3";
</script>

<template>
    <aside class="sidebar fixed top-0 left-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-auto border-r border-gray-200 bg-white px-5 transition-all duration-300 xl:static xl:translate-x-0 dark:border-gray-800 dark:bg-black -translate-x-full">
        <div class="sidebar-header flex items-center gap-2 pt-8 pb-7 justify-between">
            <a href="" class="text-xl font-bold flex items-center gap-2 font-bold">
                <svg class="w-10 h-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 280" width="80" height="80"><g id="b"><circle cx="140" cy="140" r="30" style="fill:#07ADCC;"/></g><g id="e"><path d="M55,140c0-36.66,23.33-67.98,55.93-79.88l-8.55-23.49c-42.18,15.4-72.38,55.92-72.38,103.37s30.19,87.97,72.38,103.37l8.55-23.49c-32.59-11.9-55.93-43.21-55.93-79.88Z" style="fill:none;"/><path d="M140,225c-10.21,0-20-1.81-29.07-5.12l-8.55,23.49c11.74,4.29,24.41,6.63,37.62,6.63s25.88-2.34,37.62-6.63l-8.55-23.49c-9.07,3.31-18.87,5.12-29.07,5.12Z" style="fill:none;"/><path d="M250,140c0-47.45-30.19-87.97-72.38-103.37l-8.55,23.49c32.59,11.9,55.93,43.21,55.93,79.88s-23.33,67.98-55.93,79.88l8.55,23.49c42.18-15.4,72.38-55.92,72.38-103.37Z" style="fill:none;"/><path d="M140,55c10.21,0,20,1.81,29.07,5.12l8.55-23.49c-11.74-4.29-24.41-6.63-37.62-6.63s-25.88,2.34-37.62,6.63l8.55,23.49c9.07-3.31,18.87-5.12,29.07-5.12Z" style="fill:none;"/><path d="M158.81,191.68c21.09-7.7,36.19-27.96,36.19-51.68s-15.1-43.98-36.19-51.68l-18.81,51.68,18.81,51.68Z" style="fill:none;"/><path d="M121.19,191.68c5.87,2.14,12.21,3.32,18.81,3.32s12.94-1.17,18.81-3.32l-18.81-51.68-18.81,51.68Z" style="fill:none;"/><path d="M158.81,88.32c-5.87-2.14-12.21-3.32-18.81-3.32s-12.94,1.17-18.81,3.32l18.81,51.68,18.81-51.68Z" style="fill:none;"/><path d="M85,140c0,23.72,15.1,43.98,36.19,51.68l18.81-51.68-18.81-51.68c-21.09,7.7-36.19,27.96-36.19,51.68Z" style="fill:none;"/><path d="M85,140c0-19.53,10.23-36.72,25.62-46.48,5.92-3.76,8.4-11.16,6-17.75h0c-3.28-9-14.05-12.68-22.13-7.54-23.72,15.1-39.49,41.62-39.49,71.76s15.77,56.66,39.49,71.76c8.08,5.14,18.86,1.47,22.13-7.54h0c2.4-6.59-.08-13.99-6-17.75-15.39-9.76-25.62-26.95-25.62-46.48Z" style="fill:#07ADCC;"/><path d="M195,140c0,19.53-10.23,36.72-25.62,46.48-5.92,3.76-8.4,11.16-6,17.75h0c3.28,9,14.05,12.68,22.13,7.54,23.72-15.1,39.49-41.62,39.49-71.76s-15.77-56.66-39.49-71.76c-8.08-5.14-18.86-1.47-22.13,7.54h0c-2.4,6.59.08,13.99,6,17.75,15.39,9.76,25.62,26.95,25.62,46.48Z" style="fill:#07ADCC;"/><path d="M30,140c0-42.74,24.5-79.86,60.19-98.06,6.78-3.46,9.98-11.38,7.37-18.53h0c-3.08-8.46-12.87-12.33-20.89-8.25C31.21,38.32,0,85.58,0,140s31.21,101.68,76.67,124.84c8.02,4.09,17.82.21,20.89-8.25h0c2.6-7.15-.59-15.07-7.37-18.53-35.69-18.2-60.19-55.32-60.19-98.06Z" style="fill:#07ADCC;"/><path d="M250,140c0,42.74-24.5,79.86-60.19,98.06-6.78,3.46-9.98,11.38-7.37,18.53h0c3.08,8.46,12.87,12.33,20.89,8.25,45.46-23.15,76.67-70.42,76.67-124.84s-31.21-101.68-76.67-124.84c-8.02-4.09-17.82-.21-20.89,8.25h0c-2.6,7.15.59,15.07,7.37,18.53,35.69,18.2,60.19,55.32,60.19,98.06Z" style="fill:#07ADCC;"/></g></svg>
                Uptime Monitor
            </a>
        </div>

        <div class="mb-6">
            <form>
                <div class="relative">
                    <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2">
                      <svg class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04175 9.37363C3.04175 5.87693 5.87711 3.04199 9.37508 3.04199C12.8731 3.04199 15.7084 5.87693 15.7084 9.37363C15.7084 12.8703 12.8731 15.7053 9.37508 15.7053C5.87711 15.7053 3.04175 12.8703 3.04175 9.37363ZM9.37508 1.54199C5.04902 1.54199 1.54175 5.04817 1.54175 9.37363C1.54175 13.6991 5.04902 17.2053 9.37508 17.2053C11.2674 17.2053 13.003 16.5344 14.357 15.4176L17.177 18.238C17.4699 18.5309 17.9448 18.5309 18.2377 18.238C18.5306 17.9451 18.5306 17.4703 18.2377 17.1774L15.418 14.3573C16.5365 13.0033 17.2084 11.2669 17.2084 9.37363C17.2084 5.04817 13.7011 1.54199 9.37508 1.54199Z" fill=""></path>
                      </svg>
                    </span>
                    <input id="search-input" type="text" placeholder="Search..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-200 bg-transparent py-2.5 pr-14 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden xl:w-fulldark:border-gray-800 dark:bg-gray-900 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder:text-white/30">

                    <button id="search-button" class="absolute top-1/2 right-2.5 inline-flex -translate-y-1/2 items-center gap-0.5 rounded-lg border border-gray-200 bg-gray-50 px-[7px] py-[4.5px] text-xs -tracking-[0.2px] text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                        <span> ⌘ </span>
                        <span> K </span>
                    </button>
                </div>
            </form>
        </div>

        <!-- nav-->
        <div class="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear">
            <nav>
                <!-- Menu Group-->
                <div>
                    <h3 class="mb-4 text-xs leading-[20px] text-gray-400 uppercase">
                        <span class="menu-group-title">
                            MENU
                        </span>

                        <svg class="menu-group-icon mx-auto fill-current hidden" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z" fill="currentColor"></path>
                        </svg>
                    </h3>
                </div>

                <ul class="space-y-2">
                    <li>
                        <Link :href="route('dashboard')" class="flex gap-2 items-center rounded-md hover:bg-gray-100 p-2">
                            <svg class="w-6 h-6" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V8.99998C3.25 10.2426 4.25736 11.25 5.5 11.25H9C10.2426 11.25 11.25 10.2426 11.25 8.99998V5.5C11.25 4.25736 10.2426 3.25 9 3.25H5.5ZM4.75 5.5C4.75 5.08579 5.08579 4.75 5.5 4.75H9C9.41421 4.75 9.75 5.08579 9.75 5.5V8.99998C9.75 9.41419 9.41421 9.74998 9 9.74998H5.5C5.08579 9.74998 4.75 9.41419 4.75 8.99998V5.5ZM5.5 12.75C4.25736 12.75 3.25 13.7574 3.25 15V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H9C10.2426 20.75 11.25 19.7427 11.25 18.5V15C11.25 13.7574 10.2426 12.75 9 12.75H5.5ZM4.75 15C4.75 14.5858 5.08579 14.25 5.5 14.25H9C9.41421 14.25 9.75 14.5858 9.75 15V18.5C9.75 18.9142 9.41421 19.25 9 19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5V15ZM12.75 5.5C12.75 4.25736 13.7574 3.25 15 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V8.99998C20.75 10.2426 19.7426 11.25 18.5 11.25H15C13.7574 11.25 12.75 10.2426 12.75 8.99998V5.5ZM15 4.75C14.5858 4.75 14.25 5.08579 14.25 5.5V8.99998C14.25 9.41419 14.5858 9.74998 15 9.74998H18.5C18.9142 9.74998 19.25 9.41419 19.25 8.99998V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H15ZM15 12.75C13.7574 12.75 12.75 13.7574 12.75 15V18.5C12.75 19.7426 13.7574 20.75 15 20.75H18.5C19.7426 20.75 20.75 19.7427 20.75 18.5V15C20.75 13.7574 19.7426 12.75 18.5 12.75H15ZM14.25 15C14.25 14.5858 14.5858 14.25 15 14.25H18.5C18.9142 14.25 19.25 14.5858 19.25 15V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H15C14.5858 19.25 14.25 18.9142 14.25 18.5V15Z" fill="currentColor"></path>
                            </svg>

                            <span class="menu-item-text">
                                Dashboard
                            </span>
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('monitors.index')" class="flex gap-2 items-center rounded-md hover:bg-gray-100 p-2">

                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" />
                            </svg>


                            <span class="menu-item-text">
                                Monitors
                            </span>
                        </Link>
                    </li>
                    <li class="hidden">
                        <a href="profile.html" class="flex gap-2 items-center rounded-md hover:bg-gray-100 p-2">
                            <svg class="w-6 h-6" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V8.99998C3.25 10.2426 4.25736 11.25 5.5 11.25H9C10.2426 11.25 11.25 10.2426 11.25 8.99998V5.5C11.25 4.25736 10.2426 3.25 9 3.25H5.5ZM4.75 5.5C4.75 5.08579 5.08579 4.75 5.5 4.75H9C9.41421 4.75 9.75 5.08579 9.75 5.5V8.99998C9.75 9.41419 9.41421 9.74998 9 9.74998H5.5C5.08579 9.74998 4.75 9.41419 4.75 8.99998V5.5ZM5.5 12.75C4.25736 12.75 3.25 13.7574 3.25 15V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H9C10.2426 20.75 11.25 19.7427 11.25 18.5V15C11.25 13.7574 10.2426 12.75 9 12.75H5.5ZM4.75 15C4.75 14.5858 5.08579 14.25 5.5 14.25H9C9.41421 14.25 9.75 14.5858 9.75 15V18.5C9.75 18.9142 9.41421 19.25 9 19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5V15ZM12.75 5.5C12.75 4.25736 13.7574 3.25 15 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V8.99998C20.75 10.2426 19.7426 11.25 18.5 11.25H15C13.7574 11.25 12.75 10.2426 12.75 8.99998V5.5ZM15 4.75C14.5858 4.75 14.25 5.08579 14.25 5.5V8.99998C14.25 9.41419 14.5858 9.74998 15 9.74998H18.5C18.9142 9.74998 19.25 9.41419 19.25 8.99998V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H15ZM15 12.75C13.7574 12.75 12.75 13.7574 12.75 15V18.5C12.75 19.7426 13.7574 20.75 15 20.75H18.5C19.7426 20.75 20.75 19.7427 20.75 18.5V15C20.75 13.7574 19.7426 12.75 18.5 12.75H15ZM14.25 15C14.25 14.5858 14.5858 14.25 15 14.25H18.5C18.9142 14.25 19.25 14.5858 19.25 15V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H15C14.5858 19.25 14.25 18.9142 14.25 18.5V15Z" fill="currentColor"></path>
                            </svg>

                            <span class="menu-item-text">
                                Status pages
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="profile.html" class="flex gap-2 items-center rounded-md hover:bg-gray-100 p-2">

                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                            </svg>


                            <span class="menu-item-text">
                                Incidents
                            </span>
                        </a>
                    </li>
                    <li>
                        <Link :href="route('user.plans')" class="flex gap-2 items-center rounded-md hover:bg-gray-100 p-2">
                           <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                            </svg>


                            <span class="menu-item-text">
                                Plans & Billing
                            </span>
                        </Link>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- uses-->
        <div class="py-4 mt-auto">
            <div class="mb-1">
                5 of 25 monitors used.
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: 45%"></div>
            </div>
        </div>

        <!-- profile-->
        <div class="p-4 gap-2 mt-2 bg-gray-100 -ml-4 -mr-5 flex items-center text-gray-700 dark:text-gray-400">
            <a href="">
                <img class="w-10 rounded-full" src="https://demo.tailadmin.com/src/images/user/owner.jpg" alt="User">
            </a>
            <div>
                <h4 class="text-center text-sm font-semibold text-gray-800 xl:text-left dark:text-white/90">
                    Musharof Chowdhury
                </h4>
                <p class="text-xs leading-normal text-gray-500 dark:text-gray-400">
                    Free Plan
                </p>
            </div>
        </div>
    </aside>
</template>

<style scoped>

</style>
