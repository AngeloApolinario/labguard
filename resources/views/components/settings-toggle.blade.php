@props(['title', 'desc', 'active' => false])
<div class="flex items-center justify-between p-5 bg-white border border-slate-100 rounded-2xl hover:shadow-md transition">
    <div>
        <h4 class="text-sm font-bold text-slate-800">{{ $title }}</h4>
        <p class="text-[10px] text-slate-400">{{ $desc }}</p>
    </div>
    <div class="w-11 h-6 flex items-center bg-black rounded-full p-1 cursor-pointer">
        <div class="bg-white w-4 h-4 rounded-full shadow-sm transform translate-x-5"></div>
    </div>
</div>