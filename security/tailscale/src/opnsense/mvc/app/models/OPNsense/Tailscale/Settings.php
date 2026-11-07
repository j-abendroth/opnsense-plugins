<?php

/**
 *    Copyright (C) 2024 Sheridan Computers
 *    All rights reserved.
 *
 *    Redistribution and use in source and binary forms, with or without
 *    modification, are permitted provided that the following conditions are met:
 *
 *    1. Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *    2. Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 *    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 *    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 *    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 *    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 *    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 *    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 *    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 *    POSSIBILITY OF SUCH DAMAGE.
 */

namespace OPNsense\Tailscale;

use OPNsense\Base\BaseModel;
use OPNsense\Base\Messages\Message;

class Settings extends BaseModel
{
    /**
     * whether tailscaled needs the "tailscale" pf anchors, which it only
     * uses to load its SNAT rules when routing subnets in the kernel
     * @return bool
     */
    public function pfAnchorsWanted()
    {
        return (string)$this->enabled == '1' && (string)$this->kernelRouting == '1' &&
            (string)$this->disableSNAT != '1';
    }

    public function performValidation($validateFullModel = false)
    {
        $messages = parent::performValidation($validateFullModel);

        if (
            $validateFullModel ||
            $this->kernelRouting->isFieldChanged() ||
            $this->disableSNAT->isFieldChanged()
        ) {
            /* userspace (netstack) subnet routing cannot preserve source addresses */
            if ((string)$this->disableSNAT == '1' && (string)$this->kernelRouting != '1') {
                $messages->appendMessage(new Message(
                    gettext('Disabling SNAT requires kernel subnet routing.'),
                    $this->disableSNAT->getInternalXMLTagName()
                ));
            }
        }

        return $messages;
    }
}
