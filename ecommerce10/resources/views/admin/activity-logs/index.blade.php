<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-gray-900">Admin Activity Logs</h2>
            <p class="mt-1 text-sm text-gray-500">Audit trail of administrative changes and mutation attempts.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <form method="GET" action="{{ route('activity-logs.index') }}" class="grid gap-3 border-b border-gray-100 p-4 sm:grid-cols-2 lg:grid-cols-5">
                    <label class="lg:col-span-2">
                        <span class="mb-1 block text-xs font-semibold text-gray-500">Search actor, route, or subject ID</span>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search activity..." class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-gray-500">Action</span>
                        <select name="action" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All actions</option>
                            @foreach ($actions as $action)
                                <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-gray-500">From</span>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-gray-500">To</span>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </label>
                    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-5">
                        <button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Apply filters</button>
                        <a href="{{ route('activity-logs.index') }}" class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">Reset</a>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px] divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3.5 font-semibold">Time</th>
                                <th class="px-5 py-3.5 font-semibold">Administrator</th>
                                <th class="px-5 py-3.5 font-semibold">Action</th>
                                <th class="px-5 py-3.5 font-semibold">Subject</th>
                                <th class="px-5 py-3.5 font-semibold">Result</th>
                                <th class="px-5 py-3.5 font-semibold">IP address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($logs as $log)
                                <tr class="align-top hover:bg-gray-50/70">
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-500">{{ $log->created_at->format('d M Y, H:i:s') }}</td>
                                    <td class="px-5 py-4"><p class="font-semibold text-gray-900">{{ $log->actor_name }}</p><p class="mt-1 text-xs text-gray-500">{{ $log->actor_email }}</p></td>
                                    <td class="px-5 py-4"><p class="font-medium text-gray-900">{{ $log->action }}</p><p class="mt-1 text-xs text-gray-500">{{ $log->method }} · {{ $log->route_name ?: $log->metadata['path'] ?? '—' }}</p></td>
                                    <td class="px-5 py-4"><p class="text-gray-700">{{ $log->subject_type ? class_basename($log->subject_type) : '—' }}</p><p class="mt-1 break-all text-xs text-gray-500">{{ $log->subject_id ?: '—' }}</p></td>
                                    <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $log->response_status < 400 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">HTTP {{ $log->response_status }}</span></td>
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-500">{{ $log->ip_address ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No administrative activity has been recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($logs->hasPages())
                    <div class="border-t border-gray-100 px-5 py-4">{{ $logs->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
