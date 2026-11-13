<?php
/**
 * View Partial: Pagination
 */
?>
<?php if (!empty($total_pages) && $total_pages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="javascript:void(0)" onclick="changePage(<?php echo ($page - 1); ?>)">Trước</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="javascript:void(0)" onclick="changePage(<?php echo $i; ?>)" class="<?php echo ($page == $i) ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>
    <?php if ($page < $total_pages): ?>
        <a href="javascript:void(0)" onclick="changePage(<?php echo ($page + 1); ?>)">Sau</a>
    <?php endif; ?>
</div>
<?php endif; ?>

