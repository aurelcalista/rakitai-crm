<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse text-xs">
        <thead>
            <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                <th class="py-3.5 px-4">Kode</th>
                <th class="py-3.5 px-3">Nama</th>
                <th class="py-3.5 px-3">Deskripsi</th>
                <th class="py-3.5 px-3 text-center">Dipakai</th>
                <th class="py-3.5 px-3 text-center">Status</th>
                <th class="py-3.5 px-4 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($items as $item)
            <tr class="hover:bg-slate-50/80 transition">
                <td class="py-3.5 px-4 font-mono text-[11px] font-bold text-purple-700">{{ $item['kode'] }}</td>
                <td class="py-3.5 px-3 font-bold text-slate-900">{{ $item['nama'] }}</td>
                <td class="py-3.5 px-3 text-slate-500 max-w-[220px]">{{ $item['deskripsi'] }}</td>
                <td class="py-3.5 px-3 text-center font-bold text-slate-700">{{ $item['jumlah'] }}</td>
                <td class="py-3.5 px-3 text-center">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $item['status'] === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-600 border border-red-200' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $item['status'] === 'Aktif' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                        {{ $item['status'] }}
                    </span>
                </td>
                <td class="py-3.5 px-4 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <button @click="selectedItem = {{ json_encode($item) }}; modalEdit = true" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer">Edit</button>
                        <form action="{{ route('admin.master-data.toggle-status', $item['id']) }}" method="POST" class="inline" onsubmit="return confirm('{{ $item['status'] === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' }} master data ini?');">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer {{ $item['status'] === 'Aktif' ? 'bg-amber-50 hover:bg-amber-100 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700' }}">{{ $item['status'] === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </form>
                        <form action="{{ route('admin.master-data.destroy', $item['id']) }}" method="POST" class="inline" onsubmit="return confirm('Hapus master data ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer" title="Hapus">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
