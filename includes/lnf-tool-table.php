<table class="wikitable lnf-tool-table">
    <?php
    $lastLab = 0;
    $lastProcTech = 0;
    foreach($tools as $t){
        if ($lnf->isToolIncluded($t, $args)){
            if ($t->LabID != $lastLab){
                $lastLab = $t->LabID;
                ?>
                <tr>
                    <td class="lnf-lab" data-id="<?php echo $t->LabID; ?>"><?php echo $t->LabDisplayName; ?></td>
                </tr>
                <?php
            }
            
            if ($t->ProcessTechID != $lastProcTech){
                $lastProcTech = $t->ProcessTechID;
                ?>
                <tr>
                    <td class="lnf-proc-tech" data-id="<?php echo $t->ProcessTechID; ?>"><?php echo $t->ProcessTechName; ?></td>
                </tr>
                <?php
            }
            ?>
            <tr>
                <td class="lnf-resource-name" data-id="<?php echo $t->ResourceID; ?>"><a href="<?php echo $t->WikiPageUrl; ?>"><?php echo $t->ResourceName; ?></a></td>
            </tr>
            <?php
        }
    }
    ?>
</table>