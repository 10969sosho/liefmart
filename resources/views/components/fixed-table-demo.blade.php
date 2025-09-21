{{-- 
    Fixed Table Demo Component
    Shows how to use the fixed table scrollbar functionality
--}}

<div class="card mb-4">
    <div class="card-header pb-0">
        <h6>Fixed Table Scrollbar Demo</h6>
        <p class="text-sm text-muted mb-0">
            Table with fixed scrollbar at the bottom of the screen.
        </p>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i>
            Scroll horizontally using the scrollbar at the bottom of the screen.
        </div>
        
        <div class="table-responsive border rounded shadow-sm">
            <table class="table table-hover">
                <thead class="bg-light">
                    <tr>
                        <th>No</th>
                        <th>Column 1</th>
                        <th>Column 2</th>
                        <th>Column 3</th>
                        <th>Column 4</th>
                        <th>Column 5</th>
                        <th>Column 6</th>
                        <th>Column 7</th>
                        <th>Column 8</th>
                        <th>Column 9</th>
                        <th>Column 10</th>
                        <th>Column 11</th>
                        <th>Column 12</th>
                        <th>Column 13</th>
                        <th>Column 14</th>
                        <th>Column 15</th>
                    </tr>
                </thead>
                <tbody>
                    @for ($i = 1; $i <= 15; $i++)
                        <tr>
                            <td>{{ $i }}</td>
                            @for ($j = 1; $j <= 15; $j++)
                                <td>Data {{ $i }}-{{ $j }}</td>
                            @endfor
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            <p class="fs-sm text-muted">
                <i class="fas fa-lightbulb me-1"></i> This table uses the fixed scrollbar functionality, which keeps the horizontal scrollbar visible at the bottom of the screen. 
                No modifications needed to your existing tables - the script handles it automatically for all table-responsive elements.
            </p>
        </div>
    </div>
</div> 