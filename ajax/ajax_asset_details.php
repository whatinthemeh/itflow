<?php

require_once '../includes/ajax_header.php';

$asset_id = intval($_GET['id']);

$sql = mysqli_query($mysqli, "SELECT * FROM assets
    LEFT JOIN clients ON client_id = asset_client_id 
    LEFT JOIN contacts ON asset_contact_id = contact_id 
    LEFT JOIN locations ON asset_location_id = location_id
    LEFT JOIN asset_interfaces ON interface_asset_id = asset_id AND interface_primary = 1
    WHERE asset_id = $asset_id
    LIMIT 1
");

$row = mysqli_fetch_array($sql);
$client_id = intval($row['client_id']);
$client_name = nullable_htmlentities($row['client_name']);
$asset_id = intval($row['asset_id']);
$asset_type = nullable_htmlentities($row['asset_type']);
$asset_name = nullable_htmlentities($row['asset_name']);
$asset_description = nullable_htmlentities($row['asset_description']);
$asset_make = nullable_htmlentities($row['asset_make']);
$asset_model = nullable_htmlentities($row['asset_model']);
$asset_serial = nullable_htmlentities($row['asset_serial']);
$asset_os = nullable_htmlentities($row['asset_os']);
$asset_uri = nullable_htmlentities($row['asset_uri']);
$asset_uri_2 = nullable_htmlentities($row['asset_uri_2']);
$asset_status = nullable_htmlentities($row['asset_status']);
$asset_purchase_reference = nullable_htmlentities($row['asset_purchase_reference']);
$asset_purchase_date = nullable_htmlentities($row['asset_purchase_date']);
$asset_warranty_expire = nullable_htmlentities($row['asset_warranty_expire']);
$asset_install_date = nullable_htmlentities($row['asset_install_date']);
$asset_photo = nullable_htmlentities($row['asset_photo']);
$asset_physical_location = nullable_htmlentities($row['asset_physical_location']);
$asset_notes = nullable_htmlentities($row['asset_notes']);
$asset_created_at = nullable_htmlentities($row['asset_created_at']);
$asset_vendor_id = intval($row['asset_vendor_id']);
$asset_location_id = intval($row['asset_location_id']);
$asset_contact_id = intval($row['asset_contact_id']);

$asset_ip = nullable_htmlentities($row['interface_ip']);
$asset_ipv6 = nullable_htmlentities($row['interface_ipv6']);
$asset_nat_ip = nullable_htmlentities($row['interface_nat_ip']);
$asset_mac = nullable_htmlentities($row['interface_mac']);
$asset_network_id = intval($row['interface_network_id']);

$device_icon = getAssetIcon($asset_type);

$contact_name = nullable_htmlentities($row['contact_name']);
$contact_email = nullable_htmlentities($row['contact_email']);
$contact_phone = nullable_htmlentities($row['contact_phone']);
$contact_mobile = nullable_htmlentities($row['contact_mobile']);
$contact_archived_at = nullable_htmlentities($row['contact_archived_at']);
if ($contact_archived_at) {
    $contact_name_display = "<span class='text-danger' title='Archived'><s>$contact_name</s></span>";
} else {
    $contact_name_display = $contact_name;
}
$location_name = nullable_htmlentities($row['location_name']);
if (empty($location_name)) {
    $location_name = "-";
}
$location_archived_at = nullable_htmlentities($row['location_archived_at']);
if ($location_archived_at) {
    $location_name_display = "<span class='text-danger' title='Archived'><s>$location_name</s></span>";
} else {
    $location_name_display = $location_name;
}

// Network Interfaces
$sql_related_interfaces = mysqli_query($mysqli, "
    SELECT 
        ai.interface_id,
        ai.interface_name,
        ai.interface_description,
        ai.interface_type,
        ai.interface_mac,
        ai.interface_ip,
        ai.interface_nat_ip,
        ai.interface_ipv6,
        ai.interface_primary,
        ai.interface_notes,
        n.network_name,
        n.network_id,
        connected_interfaces.interface_id AS connected_interface_id,
        connected_interfaces.interface_name AS connected_interface_name,
        connected_assets.asset_name AS connected_asset_name,
        connected_assets.asset_id AS connected_asset_id,
        connected_assets.asset_type AS connected_asset_type
    FROM asset_interfaces AS ai
    LEFT JOIN networks AS n
      ON n.network_id = ai.interface_network_id
    LEFT JOIN asset_interface_links AS ail
      ON (ail.interface_a_id = ai.interface_id OR ail.interface_b_id = ai.interface_id)
    LEFT JOIN asset_interfaces AS connected_interfaces
      ON (
          (ail.interface_a_id = ai.interface_id AND ail.interface_b_id = connected_interfaces.interface_id)
          OR
          (ail.interface_b_id = ai.interface_id AND ail.interface_a_id = connected_interfaces.interface_id)
      )
    LEFT JOIN assets AS connected_assets
      ON connected_assets.asset_id = connected_interfaces.interface_asset_id
    WHERE 
        ai.interface_asset_id = $asset_id
        AND ai.interface_archived_at IS NULL
    ORDER BY ai.interface_name ASC
");
$interface_count = mysqli_num_rows($sql_related_interfaces);

// Related Credentials Query
$sql_related_credentials = mysqli_query($mysqli, "
    SELECT 
        logins.login_id AS login_id,
        logins.login_name,
        logins.login_description,
        logins.login_uri,
        logins.login_username,
        logins.login_password,
        logins.login_otp_secret,
        logins.login_note,
        logins.login_important,
        logins.login_contact_id,
        logins.login_vendor_id,
        logins.login_asset_id,
        logins.login_software_id
    FROM logins
    LEFT JOIN login_tags ON login_tags.login_id = logins.login_id
    LEFT JOIN tags ON tags.tag_id = login_tags.tag_id
    WHERE login_asset_id = $asset_id
      AND login_archived_at IS NULL
    GROUP BY logins.login_id
    ORDER BY login_name DESC
");
$credential_count = mysqli_num_rows($sql_related_credentials);

// Related Tickets Query
$sql_related_tickets = mysqli_query($mysqli, "SELECT * FROM tickets 
    LEFT JOIN users on ticket_assigned_to = user_id
    LEFT JOIN ticket_statuses ON ticket_status_id = ticket_status
    WHERE ticket_asset_id = $asset_id
    ORDER BY ticket_number DESC"
);
$ticket_count = mysqli_num_rows($sql_related_tickets);

// Related Recurring Tickets Query
$sql_related_recurring_tickets = mysqli_query($mysqli, "SELECT * FROM scheduled_tickets 
    WHERE scheduled_ticket_asset_id = $asset_id
    ORDER BY scheduled_ticket_next_run DESC"
);
$recurring_ticket_count = mysqli_num_rows($sql_related_recurring_tickets);

// Related Documents
$sql_related_documents = mysqli_query($mysqli, "SELECT * FROM asset_documents 
    LEFT JOIN documents ON asset_documents.document_id = documents.document_id
    WHERE asset_documents.asset_id = $asset_id 
    AND document_archived_at IS NULL 
    ORDER BY document_name DESC"
);
$document_count = mysqli_num_rows($sql_related_documents);

// Related Files
$sql_related_files = mysqli_query($mysqli, "SELECT * FROM asset_files 
    LEFT JOIN files ON asset_files.file_id = files.file_id
    WHERE asset_files.asset_id = $asset_id
    AND file_archived_at IS NULL
    ORDER BY file_name DESC"
);
$file_count = mysqli_num_rows($sql_related_files);

// Related Software Query
$sql_related_software = mysqli_query(
    $mysqli,
    "SELECT * FROM software_assets 
    LEFT JOIN software ON software_assets.software_id = software.software_id 
    WHERE software_assets.asset_id = $asset_id
    AND software_archived_at IS NULL
    ORDER BY software_name DESC"
);

$software_count = mysqli_num_rows($sql_related_software);



// Generate the HTML form content using output buffering.
ob_start();
?>
<div class="modal-header">
    <h5 class="modal-title"><i class="fa fa-fw fa-<?php echo $device_icon; ?> mr-2"></i><strong><?php echo $asset_name; ?></strong></h5>
    <button type="button" class="close text-white" data-dismiss="modal">
        <span>&times;</span>
    </button>
</div>

<div class="modal-body bg-white">

    <ul class="nav nav-pills nav-justified mb-3">
        <?php if ($interface_count) { ?>
        <li class="nav-item">
            <a class="nav-link active" data-toggle="pill" href="#pills-asset-interfaces<?php echo $asset_id; ?>"><i class="fas fa-fw fa-ethernet mr-2"></i>Interfaces (<?php echo $interface_count; ?>)</a>
        </li>
        <?php } ?>
        <?php if ($credential_count) { ?>
        <li class="nav-item">
            <a class="nav-link" data-toggle="pill" href="#pills-asset-credentials<?php echo $asset_id; ?>"><i class="fas fa-fw fa-key mr-2"></i>Credentials (<?php echo $credential_count; ?>)</a>
        </li>
        <?php } ?>
        <?php if ($ticket_count) { ?>
        <li class="nav-item">
            <a class="nav-link" data-toggle="pill" href="#pills-asset-tickets<?php echo $asset_id; ?>"><i class="fas fa-fw fa-life-ring mr-2"></i>Tickets (<?php echo $ticket_count; ?>)</a>
        </li>
        <?php } ?>
        <?php if ($recurring_ticket_count) { ?>
        <li class="nav-item">
            <a class="nav-link" data-toggle="pill" href="#pills-asset-recurring-tickets<?php echo $asset_id; ?>"><i class="fas fa-fw fa-redo-alt mr-2"></i>Recurring Tickets (<?php echo $recurring_ticket_count; ?>)</a>
        </li>
        <?php } ?>
         <?php if ($software_count) { ?>
        <li class="nav-item">
            <a class="nav-link" data-toggle="pill" href="#pills-asset-licenses<?php echo $asset_id; ?>"><i class="fas fa-fw fa-cube mr-2"></i>Licenses (<?php echo $software_count; ?>)</a>
        </li>
        <?php } ?>
        <?php if ($document_count) { ?>
        <li class="nav-item">
            <a class="nav-link" data-toggle="pill" href="#pills-asset-documents<?php echo $asset_id; ?>"><i class="fas fa-fw fa-file-alt mr-2"></i>Documents (<?php echo $document_count; ?>)</a>
        </li>
        <?php } ?>
        <?php if ($file_count) { ?>
        <li class="nav-item">
            <a class="nav-link" data-toggle="pill" href="#pills-asset-files<?php echo $asset_id; ?>"><i class="fas fa-fw fa-briefcase mr-2"></i>Files (<?php echo $file_count; ?>)</a>
        </li>
        <?php } ?>
    </ul>

    <hr>

    <div class="tab-content">

        <?php if ($interface_count) { ?>
        <div class="tab-pane fade show active" id="pills-asset-interfaces<?php echo $asset_id; ?>">

            <div class="table-responsive-sm">
                <table class="table table-striped table-borderless table-hover table-sm">
                    <thead class="<?php if ($interface_count == 0) { echo "d-none"; } ?>">
                        <tr>
                            <th>Name / Port</th>
                            <th>Type</th>
                            <th>MAC</th>
                            <th>IP</th>
                            <th>Network</th>
                            <th>Connected To</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_array($sql_related_interfaces)) { ?>
                        <?php
                            $interface_id       = intval($row['interface_id']);
                            $interface_name     = nullable_htmlentities($row['interface_name']);
                            $interface_description = nullable_htmlentities($row['interface_description']);
                            $interface_type     = nullable_htmlentities($row['interface_type']);
                            $interface_mac      = nullable_htmlentities($row['interface_mac']);
                            $interface_ip       = nullable_htmlentities($row['interface_ip']);
                            $interface_nat_ip   = nullable_htmlentities($row['interface_nat_ip']);
                            $interface_ipv6     = nullable_htmlentities($row['interface_ipv6']);
                            $interface_primary  = intval($row['interface_primary']);
                            $network_id         = intval($row['network_id']);
                            $network_name       = nullable_htmlentities($row['network_name']);
                            $interface_notes    = nullable_htmlentities($row['interface_notes']);

                            // Prepare display text
                            $interface_mac_display = $interface_mac ?: '-';
                            $interface_ip_display  = $interface_ip ?: '-';
                            $interface_type_display = $interface_type ?: '-';
                            $network_name_display  = $network_name 
                                ? "<i class='fas fa-fw fa-network-wired mr-1'></i>$network_name" 
                                : '-';

                            // Connected interface details
                            $connected_asset_id = intval($row['connected_asset_id']);
                            $connected_asset_name = nullable_htmlentities($row['connected_asset_name']);
                            $connected_asset_type = nullable_htmlentities($row['connected_asset_type']);
                            $connected_asset_icon = getAssetIcon($connected_asset_type);
                            $connected_interface_name = nullable_htmlentities($row['connected_interface_name']);


                            // Show either "-" or "AssetName - Port"
                            if ($connected_asset_name) {
                                $connected_to_display = 
                                    "<a href='#' data-toggle='ajax-modal'
                                        data-modal-size='lg'
                                        data-ajax-url='ajax/ajax_asset_details.php'
                                        data-ajax-id='$connected_asset_id'>
                                        <strong><i class='fa fa-fw fa-$connected_asset_icon mr-1'></i>$connected_asset_name</strong> - $connected_interface_name
                                    </a>
                                ";
                            } else {
                                $connected_to_display = "-";
                            }
                        ?>
                        <tr>
                            <td>
                                <i class="fa fa-fw fa-ethernet text-secondary mr-1"></i>
                                <?php echo $interface_name; ?> <?php if($interface_primary) { echo "<small class='text-primary'>(Primary)</small>"; } ?>
                            </td>
                            <td><?php echo $interface_type_display; ?></td>
                            <td><?php echo $interface_mac_display; ?></td>
                            <td><?php echo $interface_ip_display; ?></td>
                            <td><?php echo $network_name_display; ?></td>
                            <td><?php echo $connected_to_display; ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php } ?>

        <?php if ($credential_count) { ?>
        <div class="tab-pane fade" id="pills-asset-credentials<?php echo $asset_id; ?>">
            <div class="table-responsive-sm-sm">
                <table class="table table-sm table-striped table-borderless table-hover">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>OTP</th>
                        <th>URI</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    while ($row = mysqli_fetch_array($sql_related_credentials)) {
                        $login_id = intval($row['login_id']);
                        $login_name = nullable_htmlentities($row['login_name']);
                        $login_description = nullable_htmlentities($row['login_description']);
                        $login_uri = nullable_htmlentities($row['login_uri']);
                        if (empty($login_uri)) {
                            $login_uri_display = "-";
                        } else {
                            $login_uri_display = "$login_uri";
                        }
                        $login_username = nullable_htmlentities(decryptLoginEntry($row['login_username']));
                        if (empty($login_username)) {
                            $login_username_display = "-";
                        } else {
                            $login_username_display = "$login_username";
                        }
                        $login_password = nullable_htmlentities(decryptLoginEntry($row['login_password']));
                        $login_otp_secret = nullable_htmlentities($row['login_otp_secret']);
                        $login_id_with_secret = '"' . $row['login_id'] . '","' . $row['login_otp_secret'] . '"';
                        if (empty($login_otp_secret)) {
                            $otp_display = "-";
                        } else {
                            $otp_display = "<span onmouseenter='showOTPViaLoginID($login_id)'><i class='far fa-clock'></i> <span id='otp_$login_id'><i>Hover..</i></span></span>";
                        }
                        $login_note = nullable_htmlentities($row['login_note']);
                        $login_important = intval($row['login_important']);
                        $login_contact_id = intval($row['login_contact_id']);
                        $login_vendor_id = intval($row['login_vendor_id']);
                        $login_asset_id = intval($row['login_asset_id']);
                        $login_software_id = intval($row['login_software_id']);

                        // Tags
                        $login_tag_name_display_array = array();
                        $login_tag_id_array = array();
                        $sql_login_tags = mysqli_query($mysqli, "SELECT * FROM login_tags LEFT JOIN tags ON login_tags.tag_id = tags.tag_id WHERE login_id = $login_id ORDER BY tag_name ASC");
                        while ($row = mysqli_fetch_array($sql_login_tags)) {

                            $login_tag_id = intval($row['tag_id']);
                            $login_tag_name = nullable_htmlentities($row['tag_name']);
                            $login_tag_color = nullable_htmlentities($row['tag_color']);
                            if (empty($login_tag_color)) {
                                $login_tag_color = "dark";
                            }
                            $login_tag_icon = nullable_htmlentities($row['tag_icon']);
                            if (empty($login_tag_icon)) {
                                $login_tag_icon = "tag";
                            }

                            $login_tag_id_array[] = $login_tag_id;
                            $login_tag_name_display_array[] = "<a href='client_logins.php?client_id=$client_id&tags[]=$login_tag_id'><span class='badge text-light p-1 mr-1' style='background-color: $login_tag_color;'><i class='fa fa-fw fa-$login_tag_icon mr-2'></i>$login_tag_name</span></a>";
                        }
                        $login_tags_display = implode('', $login_tag_name_display_array);

                        ?>
                        <tr>
                            <td>
                                <i class="fa fa-fw fa-key text-secondary"></i>
                                <?php echo $login_name; ?>
                            </td>
                            <td><?php echo $login_username_display; ?></td>
                            <td>
                                <button class="btn p-0" type="button" data-toggle="popover" data-trigger="focus" data-placement="top" data-content="<?php echo $login_password; ?>"><i class="fas fa-2x fa-ellipsis-h text-secondary"></i><i class="fas fa-2x fa-ellipsis-h text-secondary"></i></button>
                            </td>
                            <td><?php echo $otp_display; ?></td>
                            <td><?php echo $login_uri_display; ?></td>
                        </tr>

                        <?php

                    }

                    ?>

                    </tbody>
                </table>
            </div>
        </div>
        <!-- Include script to get TOTP code via the login ID -->
        <script src="js/credential_show_otp_via_id.js"></script>
        <?php } ?>

        <?php if ($ticket_count) { ?>
        <div class="tab-pane fade" id="pills-asset-tickets<?php echo $asset_id; ?>">
            <div class="table-responsive-sm">
                <table class="table table-sm table-striped table-borderless table-hover">
                    <thead class="text-dark">
                    <tr>
                        <th>Number</th>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned</th>
                        <th>Last Response</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    while ($row = mysqli_fetch_array($sql_related_tickets)) {
                        $ticket_id = intval($row['ticket_id']);
                        $ticket_prefix = nullable_htmlentities($row['ticket_prefix']);
                        $ticket_number = intval($row['ticket_number']);
                        $ticket_subject = nullable_htmlentities($row['ticket_subject']);
                        $ticket_priority = nullable_htmlentities($row['ticket_priority']);
                        $ticket_status_name = nullable_htmlentities($row['ticket_status_name']);
                        $ticket_status_color = nullable_htmlentities($row['ticket_status_color']);
                        $ticket_created_at = nullable_htmlentities($row['ticket_created_at']);
                        $ticket_updated_at = nullable_htmlentities($row['ticket_updated_at']);
                        if (empty($ticket_updated_at)) {
                            if ($ticket_status == "Closed") {
                                $ticket_updated_at_display = "<p>Never</p>";
                            } else {
                                $ticket_updated_at_display = "<p class='text-danger'>Never</p>";
                            }
                        } else {
                            $ticket_updated_at_display = $ticket_updated_at;
                        }
                        $ticket_closed_at = nullable_htmlentities($row['ticket_closed_at']);

                        if ($ticket_priority == "High") {
                            $ticket_priority_display = "<span class='p-2 badge badge-danger'>$ticket_priority</span>";
                        } elseif ($ticket_priority == "Medium") {
                            $ticket_priority_display = "<span class='p-2 badge badge-warning'>$ticket_priority</span>";
                        } elseif ($ticket_priority == "Low") {
                            $ticket_priority_display = "<span class='p-2 badge badge-info'>$ticket_priority</span>";
                        } else {
                            $ticket_priority_display = "-";
                        }
                        $ticket_assigned_to = intval($row['ticket_assigned_to']);
                        if (empty($ticket_assigned_to)) {
                            if ($ticket_status == 5) {
                                $ticket_assigned_to_display = "<p>Not Assigned</p>";
                            } else {
                                $ticket_assigned_to_display = "<p class='text-danger'>Not Assigned</p>";
                            }
                        } else {
                            $ticket_assigned_to_display = nullable_htmlentities($row['user_name']);
                        }

                        ?>

                        <tr>
                            <td>
                                <a href="ticket.php?client_id=<?php echo $client_id; ?>&ticket_id=<?php echo $ticket_id; ?>">
                                    <?php echo "$ticket_prefix$ticket_number"; ?>
                                </a>
                            </td>
                            <td><a href="ticket.php?client_id=<?php echo $client_id; ?>&ticket_id=<?php echo $ticket_id; ?>"><?php echo $ticket_subject; ?></a></td>
                            <td><?php echo $ticket_priority_display; ?></td>
                            <td>
                                <span class='badge badge-pill text-light p-2' style="background-color: <?php echo $ticket_status_color; ?>"><?php echo $ticket_status_name; ?></span>
                            </td>
                            <td><?php echo $ticket_assigned_to_display; ?></td>
                            <td><?php echo $ticket_updated_at_display; ?></td>
                        </tr>

                        <?php

                    }

                    ?>

                    </tbody>
                </table>
            </div>
        </div>
        <?php } ?>

        <?php if ($recurring_ticket_count) { ?>
        <div class="tab-pane fade" id="pills-asset-recurring-tickets<?php echo $asset_id; ?>">

            <div class="table-responsive-sm">
                <table class="table table-sm table-striped table-borderless table-hover">
                    <thead class="text-dark">
                    <tr>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Frequency</th>
                        <th>Next Run</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    while ($row = mysqli_fetch_array($sql_related_recurring_tickets)) {
                        $scheduled_ticket_id = intval($row['scheduled_ticket_id']);
                        $scheduled_ticket_subject = nullable_htmlentities($row['scheduled_ticket_subject']);
                        $scheduled_ticket_priority = nullable_htmlentities($row['scheduled_ticket_priority']);
                        $scheduled_ticket_frequency = nullable_htmlentities($row['scheduled_ticket_frequency']);
                        $scheduled_ticket_next_run = nullable_htmlentities($row['scheduled_ticket_next_run']);
                    ?>

                        <tr>
                            <td class="text-bold"><?php echo $scheduled_ticket_subject ?></td>
                            <td><?php echo $scheduled_ticket_priority ?></td>
                            <td><?php echo $scheduled_ticket_frequency ?></td>
                            <td><?php echo $scheduled_ticket_next_run ?></td>
                        </tr>

                    <?php } ?>

                    </tbody>
                </table>
            </div>
        </div>
        <?php } ?>

        <?php if ($software_count) { ?>
        <div class="tab-pane fade" id="pills-asset-licenses<?php echo $asset_id; ?>">
            <div class="table-responsive-sm">
                <table class="table table-striped table-borderless table-hover">
                    <thead class="text-dark">
                    <tr>
                        <th>Software</th>
                        <th>Type</th>
                        <th>Key</th>
                        <th>Seats</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    while ($row = mysqli_fetch_array($sql_related_software)) {
                        $software_id = intval($row['software_id']);
                        $software_name = nullable_htmlentities($row['software_name']);
                        $software_version = nullable_htmlentities($row['software_version']);
                        $software_type = nullable_htmlentities($row['software_type']);
                        $software_license_type = nullable_htmlentities($row['software_license_type']);
                        $software_key = nullable_htmlentities($row['software_key']);
                        $software_seats = nullable_htmlentities($row['software_seats']);
                        $software_purchase = nullable_htmlentities($row['software_purchase']);
                        $software_expire = nullable_htmlentities($row['software_expire']);
                        $software_notes = nullable_htmlentities($row['software_notes']);

                        $seat_count = 0;

                        // Get Login
                        $login_id = intval($row['login_id']);
                        $login_username = nullable_htmlentities(decryptLoginEntry($row['login_username']));
                        $login_password = nullable_htmlentities(decryptLoginEntry($row['login_password']));

                        // Asset Licenses
                        $asset_licenses_sql = mysqli_query($mysqli, "SELECT asset_id FROM software_assets WHERE software_id = $software_id");
                        $asset_licenses_array = array();
                        while ($row = mysqli_fetch_array($asset_licenses_sql)) {
                            $asset_licenses_array[] = intval($row['asset_id']);
                            $seat_count = $seat_count + 1;
                        }
                        $asset_licenses = implode(',', $asset_licenses_array);

                        // Contact Licenses
                        $contact_licenses_sql = mysqli_query($mysqli, "SELECT contact_id FROM software_contacts WHERE software_id = $software_id");
                        $contact_licenses_array = array();
                        while ($row = mysqli_fetch_array($contact_licenses_sql)) {
                            $contact_licenses_array[] = intval($row['contact_id']);
                            $seat_count = $seat_count + 1;
                        }
                        $contact_licenses = implode(',', $contact_licenses_array);

                        ?>
                        <tr>
                            <td><?php echo "$software_name<br><span class='text-secondary'>$software_version</span>"; ?></td>
                            <td><?php echo $software_type; ?></td>
                            <td><?php echo $software_key; ?></td>
                            <td><?php echo "$seat_count / $software_seats"; ?></td>
                        </tr>

                        <?php

                    }

                    ?>

                    </tbody>
                </table>
            </div>
        </div>
        <?php } ?>

        <?php if ($document_count) { ?>
        <div class="tab-pane fade" id="pills-asset-documents<?php echo $asset_id; ?>">

            <div class="table-responsive-sm">
                <table class="table table-sm table-striped table-borderless table-hover">
                    <thead class="text-dark">
                    <tr>
                        <th>Document Title</th>
                        <th>By</th>
                        <th>Created</th>
                        <th>Updated</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    while ($row = mysqli_fetch_array($sql_related_documents)) {
                        $document_id = intval($row['document_id']);
                        $document_name = nullable_htmlentities($row['document_name']);
                        $document_description = nullable_htmlentities($row['document_description']);
                        $document_created_by = nullable_htmlentities($row['user_name']);
                        $document_created_at = nullable_htmlentities($row['document_created_at']);
                        $document_updated_at = nullable_htmlentities($row['document_updated_at']);

                        $linked_documents[] = $document_id;

                        ?>

                        <tr>
                            <td>
                                <a href="#"
                                    data-toggle="ajax-modal"
                                    data-modal-size="lg"
                                    data-ajax-url="ajax/ajax_document_view.php"
                                    data-ajax-id="<?php echo $document_id; ?>"
                                    >
                                    <?php echo $document_name; ?>
                                </a>
                                <div class="text-secondary"><?php echo $document_description; ?></div>
                            </td>
                            <td><?php echo $document_created_by; ?></td>
                            <td><?php echo $document_created_at; ?></td>
                            <td><?php echo $document_updated_at; ?></td>
                        </tr>

                        <?php

                    }

                    ?>

                    </tbody>
                </table>
            </div>
        </div>
        <?php } ?>

        <?php if ($file_count) { ?>
        <div class="tab-pane fade" id="pills-asset-files<?php echo $asset_id; ?>">
            <div class="table-responsive-sm">
                <table class="table table-sm table-striped table-borderless table-hover">
                    <thead class="text-dark">
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Uploaded</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    while ($row = mysqli_fetch_array($sql_related_files)) {
                        $file_id = intval($row['file_id']);
                        $file_name = nullable_htmlentities($row['file_name']);
                        $file_mime_type = nullable_htmlentities($row['file_mime_type']);
                        $file_description = nullable_htmlentities($row['file_description']);
                        $file_reference_name = nullable_htmlentities($row['file_reference_name']);
                        $file_ext = nullable_htmlentities($row['file_ext']);
                        if ($file_ext == 'pdf') {
                            $file_icon = "file-pdf";
                        } elseif ($file_ext == 'gz' || $file_ext == 'tar' || $file_ext == 'zip' || $file_ext == '7z' || $file_ext == 'rar') {
                            $file_icon = "file-archive";
                        } elseif ($file_ext == 'txt' || $file_ext == 'md') {
                            $file_icon = "file-alt";
                        } elseif ($file_ext == 'msg') {
                            $file_icon = "envelope";
                        } elseif ($file_ext == 'doc' || $file_ext == 'docx' || $file_ext == 'odt') {
                            $file_icon = "file-word";
                        } elseif ($file_ext == 'xls' || $file_ext == 'xlsx' || $file_ext == 'ods') {
                            $file_icon = "file-excel";
                        } elseif ($file_ext == 'pptx' || $file_ext == 'odp') {
                            $file_icon = "file-powerpoint";
                        } elseif ($file_ext == 'mp3' || $file_ext == 'wav' || $file_ext == 'ogg') {
                            $file_icon = "file-audio";
                        } elseif ($file_ext == 'mov' || $file_ext == 'mp4' || $file_ext == 'av1') {
                            $file_icon = "file-video";
                        } elseif ($file_ext == 'jpg' || $file_ext == 'jpeg' || $file_ext == 'png' || $file_ext == 'gif' || $file_ext == 'webp' || $file_ext == 'bmp' || $file_ext == 'tif') {
                            $file_icon = "file-image";
                        } else {
                            $file_icon = "file";
                        }
                        $file_created_at = nullable_htmlentities($row['file_created_at']);
                        ?>
                        <tr>
                            <td><a class="text-dark" href="<?php echo "uploads/clients/$client_id/$file_reference_name"; ?>" target="_blank" ><?php echo "$file_name<br><span class='text-secondary'>$file_description</span>"; ?></a></td>
                            <td><?php echo $file_mime_type; ?></td>
                            <td><?php echo $file_created_at; ?></td>
                        </tr>

                        <?php

                    }

                    ?>

                    </tbody>
                </table>
            </div>
        </div>
        <?php } ?>           

    </div>

</div>

<div class="modal-footer bg-white">
    <a href="asset_details.php?asset_id=<?php echo $asset_id; ?>" class="btn btn-primary text-bold"><span class="text-white">More Details</span></a>
    <button type="button" class="btn btn-light" data-dismiss="modal"><i class="fa fa-times mr-2"></i>Close</button>
</div>

<?php
require_once "../includes/ajax_footer.php";
