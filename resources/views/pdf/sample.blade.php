<x-pdf-layout :title="'PDF rendering check'">
    <h1>PDF rendering check / PDF 출력 확인 / PDF 输出检查</h1>
    <table>
        <thead>
            <tr><th>Course</th><th>교과목</th><th lang="zh-CN">课程</th><th lang="lo">ວິຊາ</th><th>Grade</th></tr>
        </thead>
        <tbody>
            <tr><td>Data Structures</td><td>자료구조</td><td lang="zh-CN">数据结构</td><td lang="lo">ໂຄງສ້າງຂໍ້ມູນ</td><td>A+</td></tr>
            <tr><td>Nursing Practice</td><td>간호학 실습</td><td lang="zh-CN">护理学实习</td><td lang="lo">ການຝຶກປະຕິບັດພະຍາບານ</td><td>B0</td></tr>
            <tr><td>Korean Language</td><td>한국어 (읽기·쓰기)</td><td lang="zh-CN">韩国语（读写）</td><td lang="lo">ພາສາເກົາຫຼີ (ອ່ານ·ຂຽນ)</td><td>P</td></tr>
        </tbody>
    </table>
    <p>Issued {{ now()->toDateString() }} · 발급일 · 签发日期</p>
</x-pdf-layout>
