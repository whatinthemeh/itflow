<div class="modal" id="linkCredentialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-fw fa-key mr-2"></i>Link Credential to <strong><?php echo $asset_name; ?></strong></h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="post.php" method="post" autocomplete="off">
                <input type="hidden" name="asset_id" value="<?php echo $asset_id; ?>">
                <div class="modal-body bg-white">

                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-key"></i></span>
                            </div>
                            <select class="form-control select2" name="credential_id">
                                <option value="">- Select a Credential -</option>
                                <?php
            
                                $sql_credentials_select = mysqli_query($mysqli, "SELECT credential_id, credential_name FROM credentials
                                    WHERE credential_client_id = $client_id
                                    AND credential_asset_id != $contact_id
                                    AND credential_asset_id = 0
                                    AND credential_archived_at IS NULL
                                    ORDER BY credential_name ASC"
                                );
                                while ($row = mysqli_fetch_array($sql_credentials_select)) {
                                    $credential_id = intval($row['credential_id']);
                                    $credential_name = nullable_htmlentities($row['credential_name']);

                                    ?>
                                    <option value="<?php echo $credential_id ?>"><?php echo $credential_name; ?></option>
                                    <?php
                                }
                                ?>

                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="submit" name="link_asset_to_credential" class="btn btn-primary text-bold"><i class="fa fa-check mr-2"></i>Link</button>
                    <button type="button" class="btn btn-light" data-dismiss="modal"><i class="fa fa-times mr-2"></i>Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
