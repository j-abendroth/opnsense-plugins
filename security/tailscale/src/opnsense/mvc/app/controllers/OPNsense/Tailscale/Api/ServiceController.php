<?php

/*
 * Copyright (C) 2024 Sheridan Computers
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace OPNsense\Tailscale\Api;

use OPNsense\Base\ApiMutableServiceControllerBase;
use OPNsense\Core\Backend;

/**
 * Class ServiceController
 * @package OPNsense\Tailscale
 */
class ServiceController extends ApiMutableServiceControllerBase
{
    protected static $internalServiceClass = '\OPNsense\Tailscale\Settings';
    protected static $internalServiceEnabled = 'enabled';
    protected static $internalServiceTemplate = 'OPNsense/Tailscale';
    protected static $internalServiceName = 'tailscale';

    /**
     * Reload the filter only when the "tailscale" pf anchor references in the
     * running ruleset do not match what the current settings want.
     *
     * tailscaled only uses these anchors when it routes subnets in the kernel
     * with SNAT enabled: on start it loads its per-interface NAT rules into
     * them, and it refuses to reload the main ruleset to add the references
     * itself. If they are missing at that point it does not retry until it is
     * restarted. The references are therefore wanted only while the service is
     * enabled, kernel routing is on and SNAT is not disabled.
     *
     * - Wanted but missing: reload to add them. This runs while tailscaled is
     *   stopped, so they are in place before it starts and loads its rules.
     * - Present but no longer wanted (kernel routing, SNAT or the service was
     *   switched off): reload to remove them, so a stale SNAT preference in
     *   tailscaled cannot silently translate forwarded traffic.
     * - Already matching: skip the reload, so applying unrelated changes, or
     *   running with the default userspace routing, leaves the firewall alone.
     */
    protected function invokeFirewallReload()
    {
        $present = trim((new Backend())->configdRun('tailscale pf-anchor-status')) === 'present';

        return $this->getModel()->pfAnchorsWanted() !== $present;
    }
}
