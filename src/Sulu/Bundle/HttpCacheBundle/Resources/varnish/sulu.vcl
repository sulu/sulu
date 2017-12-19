/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

C{
    #include <stdlib.h>
}C

sub sulu_recv {
    if (req.method == "PURGE") {
        if (!client.ip ~ invalidators) {
            return (synth(405, "Not allowed"));
        }
        return (purge);
    }

    if (req.method == "BAN") {
        if (!client.ip ~ invalidators) {
            return (synth(405, "Not allowed"));
        }


        if (req.http.x-cache-tags) {
            ban("obj.http.x-host ~ " + req.http.x-host
                + " && obj.http.x-url ~ " + req.http.x-url
                + " && obj.http.content-type ~ " + req.http.x-content-type
                + " && obj.http.x-cache-tags ~ " + req.http.x-cache-tags
            );
        } else {
            ban("obj.http.x-host ~ " + req.http.x-host
                + " && obj.http.x-url ~ " + req.http.x-url
                + " && obj.http.content-type ~ " + req.http.x-content-type
            );
        }

        return (synth(200, "Banned"));
    }

    if (req.method != "GET" && req.method != "HEAD") {
        return (pass);
    }

    if (req.http.Authorization) {
        return (pass);
    }

    if (req.http.Cache-Control ~ "no-cache" && client.ip ~ invalidators) {
        set req.hash_always_miss = true;
    }
}

sub sulu_backend_response {
    # Set ban-lurker friendly custom headers
    set beresp.http.x-url = bereq.url;
    set beresp.http.x-host = bereq.http.host;

    // Check for ESI acknowledgement and remove Surrogate-Control header
    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
        unset beresp.http.Surrogate-Control;
        set beresp.do_esi = true;
    }

    if (beresp.http.X-Reverse-Proxy-TTL) {
        /*
         * Note that there is a ``beresp.ttl`` field in VCL but unfortunately
         * it can only be set to absolute values and not dynamically. Thus we
         * have to resort to an inline C code fragment.
         *
         * As of Varnish 4.0, inline C is disabled by default. To use this
         * feature, you need to add `-p vcc_allow_inline_c=on` to your Varnish
         * startup command.
         */
        C{
            const char *ttl;
            const struct gethdr_s hdr = { HDR_BERESP, "\024X-Reverse-Proxy-TTL:" };
            ttl = VRT_GetHdr(ctx, &hdr);
            VRT_l_beresp_ttl(ctx, atoi(ttl));
        }C

        unset beresp.http.X-Reverse-Proxy-TTL;
    }
}

sub sulu_deliver {
    # Add X-Cache header if debugging is enabled
    if (resp.http.X-Cache-Debug) {
        if (obj.hits > 0) {
            set resp.http.X-Cache = "HIT";
        } else {
            set resp.http.X-Cache = "MISS";
        }
    }
}
