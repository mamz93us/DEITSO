<x-filament-panels::page>
    <div class="space-y-6">
        @forelse($this->getCustomDomains() as $d)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">{{ $d['host'] }}</h3>
                        <p class="text-sm text-gray-500">
                            DNS: <span class="font-mono">{{ $d['dns_status'] }}</span> ·
                            TLS: <span class="font-mono">{{ $d['tls_status'] }}</span>
                        </p>
                    </div>
                </div>

                <div class="mt-4 space-y-3 text-sm">
                    <div>
                        <p class="font-medium">1. Add this CNAME record:</p>
                        <pre class="bg-gray-50 dark:bg-gray-900 rounded p-2 mt-1 text-xs">
Type:  CNAME
Host:  {{ $d['host'] }}
Value: {{ $d['cname_target'] }}
TTL:   600</pre>
                    </div>

                    <div>
                        <p class="font-medium">2. Add this TXT record for verification:</p>
                        <pre class="bg-gray-50 dark:bg-gray-900 rounded p-2 mt-1 text-xs">
Type:  TXT
Host:  {{ $d['txt_host'] }}
Value: {{ $d['token'] }}
TTL:   600</pre>
                    </div>

                    <div>
                        <p class="font-medium">3. After saving the records, wait a few minutes for propagation.</p>
                        <p class="mt-1 text-gray-500">We re-check every hour automatically, or click below to recheck now.</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-gray-500">
                <p>No custom domains configured yet.</p>
                <p class="text-sm mt-1">Add one in the <strong>Domains</strong> section first, then return here for the DNS instructions.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
